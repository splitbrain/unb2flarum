<?php
/** @noinspection SqlResolve */

namespace splitbrain\unb2flarum\import;


class Categories extends AbstractImport
{

    public function import()
    {
        $this->importCategories();
    }

    /**
     * Categories are imported as 1st level Tags ordered by the category ID
     */
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
                `Description`,
                `ID`
              FROM {$unb}Forums
             WHERE Flags = 0
        ";

        $insert = "
        INSERT INTO {$flarum}tags SET
            `id` = ?,
            `name` = ?,
            `slug` = ?,
            `description` = ?,
            `position` = ?,
            `color` = ?
        ";

        $result = $pdo->query($select);
        $sth = $pdo->prepare($insert);

        while ($row = $result->fetch()) {
            $row[2] = $this->slugify->slugify($row[2]); // create slug
            $row[5] =  sprintf('#%06X', mt_rand(0, 0xFFFFFF)); // random color
            // we do not catch Exceptions here, because we consider categories vital
            $sth->execute($row);
        }
    }
}
