<?php
/** @noinspection SqlResolve */

namespace splitbrain\unb2flarum\import;


class Categories extends AbstractImport
{

    public function import()
    {
        $this->importCategories();
    }

    protected function importCategories()
    {
        $pdo = $this->db->getPDO();
        $unb = $this->db->getUnbPrefix();
        $flarum = $this->db->getFlarumPrefix();
        $this->logger->notice('Importing Categories...');

        $select = "
            SELECT
                `ID`,
                `Name`,
                `Name`,
                `Description`
              FROM {$unb}Forums
             WHERE Flags = 0
        ";

        $insert = "
        INSERT INTO {$flarum}tags SET
            `id` = ?,
            `name` = ?,
            `slug` = ?,
            `description` = ?
        ";

        $result = $pdo->query($select);
        $sth = $pdo->prepare($insert);

        while ($row = $result->fetch()) {
            $row[3] = $this->slugify->slugify($row[3]); // create slug
            // we do not catch Exceptions here, because we consider categories vital
            $sth->execute($row);
        }
    }
}
