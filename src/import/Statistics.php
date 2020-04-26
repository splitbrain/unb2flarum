<?php

/** @noinspection SqlResolve */

namespace splitbrain\unb2flarum\import;


class Statistics extends AbstractImport
{
    public function import()
    {
        $this->updateStatistics();
    }

    protected function updateStatistics()
    {
        $pdo = $this->db->getPDO();
        $flarum = $this->db->getFlarumPrefix();
        $this->logger->notice('Importing Thread Statistics...');

        $sql = "
            UPDATE {$flarum}discussions AS A
               SET comment_count       = (SELECT COUNT(*)        FROM {$flarum}posts WHERE discussion_id = A.id),
                   participant_count   = (SELECT COUNT(DISTINCT `user_id`) FROM {$flarum}posts WHERE discussion_id = A.id), 
                   post_number_index   = (SELECT MAX(`number`)   FROM {$flarum}posts WHERE discussion_id = A.id),
                   first_post_id       = (SELECT MIN(B.ID)       FROM {$flarum}posts AS B WHERE discussion_id = A.id),
                   last_posted_at      = (SELECT MAX(created_at) FROM {$flarum}posts WHERE discussion_id = A.id),
                   last_posted_user_id = (SELECT user_id         FROM {$flarum}posts WHERE discussion_id = A.id ORDER BY `created_at` DESC LIMIT 1),
                   last_post_id        = (SELECT MAX(C.ID)       FROM {$flarum}posts AS C WHERE discussion_id = A.id),
                   last_post_number    = (SELECT MAX(`number`)   FROM {$flarum}posts WHERE discussion_id = A.id)
        ";

        $pdo->exec($sql);
    }

}
