<?php
/** @noinspection SqlResolve */

namespace splitbrain\unb2flarum\import;

use PDOException;

class Users extends AbstractImport
{
    public function import()
    {
        $this->importUsers();
        $this->importProfile();
    }

    protected function importUsers()
    {
        $pdo = $this->db->getPDO();
        $unb = $this->db->getUnbPrefix();
        $flarum = $this->db->getFlarumPrefix();
        $this->logger->notice('Importing Users...');

        $select = "
            SELECT
                `ID`,
                `Name`,
                `EMail`,
                `ValidatedEmail` != '',
                '',
                CONCAT('{\"type\":\"kmd5\", \"password\":\"', `password`, '\"}'),
                `About`,
                `Avatar`,
                FROM_UNIXTIME(RegDate),
                FROM_UNIXTIME(LastActivity)
            FROM {$unb}Users
            ";

        $insert = "
            INSERT INTO {$flarum}users SET
                 `id` = ?,
                 `username` = ?,
                 `email` = ?,
                 `is_email_confirmed` = ?,
                 `password` = ?,
                 `migratetoflarum_old_password` = ?,
                 `bio` = ?,
                 `avatar_url` = ?,
                 `joined_at` = ?,
                 `last_seen_at` = ?
        ";


        $result = $pdo->query($select);
        $sth = $pdo->prepare($insert);

        while ($row = $result->fetch()) {
            $orguser = $row[1];
            $mod = '';

            RETRY:
            try {
                $row[1] = $this->slugify->slugify($row[1] . $mod); // clean up user
                $row[7] = ($row[7] == 'gravatar') ? '' : $row[7]; // remove 'gravatar' marker from avatar url
                $sth->execute($row);
            } catch (PDOException $e) {
                // catch duplicate user names and try to circumvent it
                if ($e->errorInfo[1] === 1062 && (strpos($e->getMessage(), 'flarum_users_username_unique') !== false)) {
                    if ($row[1] === '') {
                        $row[1] = 'user';
                    } else {
                        $mod = ((int)$mod) + 1;
                    }
                    goto RETRY; // yes I know
                }

                // this user failed to import
                $this->logger->error(
                    'User {user} ({id}) skipped. {msg}',
                    ['user' => $orguser, 'id' => $row[0], 'msg' => $e->getMessage()]
                );
            }
        }
    }

    protected function importProfile()
    {
        $pdo = $this->db->getPDO();
        $unb = $this->db->getUnbPrefix();
        $flarum = $this->db->getFlarumPrefix();

        // get the configured mapping (masquerade => UNB)
        $mapconf = $this->db->getProfileMap();
        if(empty($mapconf)) return;

        $this->logger->notice('Importing Profile Data...');

        // get the defined masquerade fields
        $sql = "SELECT id, name FROM {$flarum}fof_masquerade_fields";
        $fields = $pdo->query($sql)->fetchAll();

        // build validated mapping (id => UNB)
        $map = [];
        foreach ($fields as list($id, $field)) {
            if (isset($mapconf[$field])) {
                $map[$id] = $mapconf[$field];
            }
        }
        if (!count($map)) return;

        // prepare statements

        $fields_u = join(',', array_values($map));
        $select = "SELECT ID, {$fields_u} FROM {$unb}Users";

        $insert = "
            INSERT INTO {$flarum}fof_masquerade_answers
               SET `field_id` = ?,
                   `user_id` = ?,
                   `content` = ?,
                   `created_at` = NOW(),
                   `updated_at` = NOW()
            ";

        $result = $pdo->query($select);
        $sth = $pdo->prepare($insert);

        while ($row = $result->fetch()) {
            foreach (array_keys($map) as $i => $id) {
                $content = $row[$i + 1];
                if (!$content) continue;
                $sth->execute([$id, $row[0], $content]);
            }
        }
    }
}
