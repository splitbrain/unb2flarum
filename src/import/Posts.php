<?php

/** @noinspection SqlResolve */

namespace splitbrain\unb2flarum\import;

use s9e\TextFormatter\Bundles\Forum as TextFormatter;

class Posts extends AbstractImport
{
    protected $counter;

    public function import()
    {
        $this->importPosts();
    }

    public function importPosts()
    {
        $pdo = $this->db->getPDO();
        $unb = $this->db->getUnbPrefix();
        $flarum = $this->db->getFlarumPrefix();
        $this->logger->notice('Importing Posts...');

        $select = "
            SELECT
                `ID`,
                `Thread`,
                FROM_UNIXTIME(`Date`),
                IF(`User` > 0, `User`, NULL),
                'comment',
                `Msg`,
                FROM_UNIXTIME(`EditDate`),
                IF(`EditUser` > 0, `EditUser`, NULL),
                `IP`
              FROM {$unb}Posts
        ";

        $insert = "
            INSERT INTO {$flarum}posts SET
                `id` = ?,
                `discussion_id` = ?,
                `created_at` = ?,
                `user_id` = ?,
                `type` = ?,
                `content` = ?,
                `edited_at` = ?,
                `edited_user_id` = ?,
                `ip_address` = ?,
                `number` = ?
        ";

        $result = $pdo->query($select);
        $sth = $pdo->prepare($insert);

        while ($row = $result->fetch()) {
            // transform content from BB code into XML
            $row[5] = $this->parseContent($row[5]);
            // we need to count the posts
            $this->counter[$row[1]] = $this->counter[$row[1]] ?? 0;
            $row[9] = $this->counter[$row[1]]++;

            RETRY:
            try {
                $sth->execute($row);
            } catch (\PDOException $e) {
                if (
                    $this->fixUserReference($row, 3, $e, 'flarum_posts_user_id_foreign', 'Post') ||
                    $this->fixUserReference($row, 7, $e, 'flarum_posts_edited_user_id_foreign', 'Post')
                ) {
                    goto RETRY; // Yes, I know
                }

                // this thread failed to import
                $this->logger->error(
                    'Post {id} skipped. {msg}',
                    ['id' => $row[0], 'msg' => $e->getMessage()]
                );
            }
        }
    }

    /**
     * Parse the BBCode into XML
     *
     * @param string $content
     * @return string
     * @todo check if all formatting is correct
     */
    protected function parseContent($content)
    {
        return TextFormatter::parse($content);
    }

}
