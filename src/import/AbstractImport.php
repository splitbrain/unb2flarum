<?php

namespace splitbrain\unb2flarum\import;

use Cocur\Slugify\Slugify;
use Psr\Log\LoggerInterface;
use splitbrain\unb2flarum\Db;

abstract class AbstractImport
{
    /** @var Db */
    protected $db;
    /** @var LoggerInterface */
    protected $logger;

    protected $slugify;

    public function __construct(Db $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;

        $this->slugify = new Slugify();
        $this->slugify->activateRuleSet('chinese');
        $this->slugify->activateRuleSet('russian');
    }

    abstract public function import();

    /**
     * Checks if the given exception matches the given contraint and adjusts the userID to null
     *
     * @param array $row The row that's currently imported
     * @param int $index The index where the UserID is in
     * @param \PDOException $e The caught exception
     * @param string $match The name of the contraint to check
     * @param string $type The type of import we're doing currently
     * @return bool has the user reference been fixed?
     */
    protected function fixUserReference(&$row, $index, \PDOException $e, $match, $type = 'Record')
    {
        if ($row[$index] === null) return false;
        if ($e->errorInfo[1] !== 1452) return false; // Integrity Constraint Error
        if (strpos($e->getMessage(), $match) === false) return false;

        $this->logger->warning(
            '{type} {id} references unknown UserID {uid}. Adjusted to anonymous user.',
            ['type' => $type, 'id' => $row[0], 'uid' => $row[$index]]
        );
        $row[$index] = null;
        return true;
    }
}
