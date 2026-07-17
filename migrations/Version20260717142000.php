<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Réaligne les index (pas les contraintes, déjà correctes) sur la colonne
 * article_id de ligne_demande et mouvement_stock, oubliés lors du
 * renommage fourniture -> article. Purement cosmétique, idempotent.
 */
final class Version20260717142000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Réaligne les index article_id sur ligne_demande et mouvement_stock';
    }

    public function up(Schema $schema): void
    {
        $this->realignIndex($schema, 'ligne_demande', 'article_id', 'IDX_B90DE99C7294869C');
        $this->realignIndex($schema, 'mouvement_stock', 'article_id', 'IDX_61E2C8EB7294869C');
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
