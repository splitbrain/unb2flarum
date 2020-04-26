<?php
/** @noinspection SqlResolve */

namespace splitbrain\unb2flarum\import;

class Threads extends AbstractImport
{

    public function import()
    {
        $this->importThreads();
        $this->assignCategories();
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
            $row[2] = $this->slugify->slugify($row[2]); // create slug

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

    protected function assignCategories()
    {
        $pdo = $this->db->getPDO();
        $unb = $this->db->getUnbPrefix();
        $flarum = $this->db->getFlarumPrefix();
        $this->logger->notice('Assigning Thread Categories...');

        $sql = "
            INSERT INTO {$flarum}discussion_tag
                (`discussion_id`, `tag_id`)
            SELECT `ID`, `Forum`
              FROM {$unb}Threads
        ";

        $pdo->exec($sql);
    }
}
