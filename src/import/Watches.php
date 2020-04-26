<?php
/** @noinspection SqlResolve */

namespace splitbrain\unb2flarum\import;

class Watches extends AbstractImport
{

    public function import()
    {
        $this->importThreadWatches();
    }

    protected function importThreadWatches()
    {
        $pdo = $this->db->getPDO();
        $unb = $this->db->getUnbPrefix();
        $flarum = $this->db->getFlarumPrefix();
        $this->logger->notice('Importing Thread Watches...');

        $sql = "
            INSERT IGNORE INTO {$flarum}discussion_user
                (
                `user_id`,
                `discussion_id`,
                `last_read_at`,
                `subscription`
                )
            SELECT
                `User`,
                `Thread`,
                FROM_UNIXTIME(`LastRead`),
                IF(`Mode` > 0, 'follow', 0)
              FROM {$unb}ThreadWatch
        ";

        $pdo->exec($sql);
    }
}
