<?php
/** @noinspection SqlResolve */

namespace splitbrain\unb2flarum\import;

class Threads extends AbstractImport
{

    public function import()
    {
        $this->importThreads();
    }

    /**
     * @todo handle post options from UNB
     */
    protected function importThreads()
    {
        $pdo = $this->db->getPDO();
        $unb = $this->db->getUnbPrefix();
        $flarum = $this->db->getFlarumPrefix();
        $this->logger->notice('Importing Threads...');

        $select = "
            SELECT
                `ID`,
                `Subject`,
                `Subject`,
                FROM_UNIXTIME(`Date`),
                IF(`User` > 0, `User`, NULL)
              FROM {$unb}Threads
        ";

        $insert = "
        INSERT INTO {$flarum}discussions SET
            `id` = ?,
            `title` = ?,
            `slug` = ?,
            `created_at` = ?,
            `user_id` = ?
        ";

        $result = $pdo->query($select);
        $sth = $pdo->prepare($insert);

        while ($row = $result->fetch()) {
            $row[3] = $this->slugify->slugify($row[3]); // create slug

            RETRY:
            try {
                $sth->execute($row);
            } catch (\PDOException $e) {
                if ($this->fixUserReference($row, 4, $e, 'flarum_discussions_user_id_foreign', 'Thread')) {
                    goto RETRY; // Yes, I know
                }

                // this thread failed to import
                $this->logger->error(
                    'Thread {thread} ({id}) skipped. {msg}',
                    ['thread' => $row[1], 'id' => $row[0], 'msg' => $e->getMessage()]
                );
            }
        }
    }
}
