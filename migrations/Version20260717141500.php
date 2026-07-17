<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration corrective idempotente : le renommage fourniture -> article a fait
 * dériver le nom auto-généré des contraintes FK "demande_id" sur ligne_demande
 * et mouvement_stock (effet de bord de la convention de nommage Doctrine).
 * Purement cosmétique (aucun changement de comportement), sans impact si déjà correct.
 */
final class Version20260717141500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Réaligne les noms de contraintes FK demande_id après le renommage article';
    }

    public function up(Schema $schema): void
    {
        $this->realignFk($schema, 'ligne_demande', 'demande_id', 'demande_materiel', 'FK_B90DE99C80E95E18', 'IDX_B90DE99C80E95E18');
        $this->realignFk($schema, 'mouvement_stock', 'demande_id', 'demande_materiel', 'FK_61E2C8EB80E95E18', 'IDX_61E2C8EB80E95E18');
        $this->realignIndex($schema, 'mouvement_stock', 'operateur_id', 'IDX_61E2C8EB3F192FC');
    }

    private function realignFk(Schema $schema, string $tableName, string $column, string $foreignTable, string $newFkName, string $newIndexName): void
    {
        $table = $schema->getTable($tableName);

        foreach ($table->getForeignKeys() as $fk) {
            if ($fk->getLocalColumns() === [$column] && $fk->getName() !== $newFkName) {
                $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY `%s`', $tableName, $fk->getName()));
                $this->addSql(sprintf(
                    'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id)',
                    $tableName,
                    $newFkName,
                    $column,
                    $foreignTable
                ));
            }
        }

        $this->realignIndex($schema, $tableName, $column, $newIndexName);
    }

    private function realignIndex(Schema $schema, string $tableName, string $column, string $newIndexName): void
    {
        foreach ($schema->getTable($tableName)->getIndexes() as $index) {
            if ($index->getColumns() === [$column] && $index->getName() !== $newIndexName) {
                $this->addSql(sprintf('ALTER TABLE %s RENAME INDEX `%s` TO %s', $tableName, $index->getName(), $newIndexName));
            }
        }
    }

    public function down(Schema $schema): void
    {
        throw new \RuntimeException('Migration purement cosmétique, non réversible automatiquement.');
    }
}
