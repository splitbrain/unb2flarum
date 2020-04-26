<?php

/** @noinspection SqlResolve */

namespace splitbrain\unb2flarum\import;

class Groups extends AbstractImport
{
    /** @var array UNB to Flarum mapping for default groups */
    protected $groupmap = [
        1 => 2, //Guests
        2 => 3, //Members
        3 => 4, //Global Moderators
        4 => 1, //Administrators
    ];

    public function import()
    {
        $this->importGroups();
        $this->importGroupMemberships();
    }

    public function importGroups()
    {
        $pdo = $this->db->getPDO();
        $unb = $this->db->getUnbPrefix();
        $flarum = $this->db->getFlarumPrefix();
        $this->logger->notice('Importing Groups...');

        // skip default groups
        $select = "
            SELECT
                `ID`,
                `Name`,
                `Name`
             FROM {$unb}GroupNames
            WHERE ID > 4
        ";

        $insert = "
            INSERT INTO {$flarum}groups SET
                `ID` = ?,
                `name_singular` = ?,
                `name_plural` = ?,
                `color` = ?
        ";

        $result = $pdo->query($select);
        $sth = $pdo->prepare($insert);

        while ($row = $result->fetch()) {
            $row[3] = sprintf('#%06X', mt_rand(0, 0xFFFFFF)); // random color
            $sth->execute($row);
        }
    }

    public function importGroupMemberships()
    {
        $pdo = $this->db->getPDO();
        $unb = $this->db->getUnbPrefix();
        $flarum = $this->db->getFlarumPrefix();
        $this->logger->notice('Importing Group Memberships...');

        $select = "SELECT `User`, `Group` FROM {$unb}GroupMembers";
        $insert = "INSERT INTO {$flarum}group_user SET `user_id` = ?, `group_id` = ?";

        $result = $pdo->query($select);
        $sth = $pdo->prepare($insert);

        while ($row = $result->fetch()) {
            // remap the default groups:
            $row[1] = $this->groupmap[$row[1]] ?? $row[1];

            try {
                $sth->execute($row);
            } catch (\PDOException $e) {
                $this->logger->error(
                    'Membership rule for User {uid} with Group {gid} skipped. {msg}',
                    ['uid' => $row[0], 'gid' => $row[1], 'msg' => $e->getMessage()]
                );
            }
        }
    }
}
