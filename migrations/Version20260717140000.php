<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration corrective et idempotente : termine le renommage fourniture -> article
 * quelle que soit l'étape où l'environnement s'est arrêté (utile car la migration
 * Version20260717135708 a échoué à mi-chemin en prod à cause d'un nom de contrainte
 * FK différent de celui du dev local).
 */
final class Version20260717140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Finalise idempotemment le renommage fourniture -> article (rattrapage prod)';
    }

    public function up(Schema $schema): void
    {
        $tableName = $schema->hasTable('fourniture') ? 'fourniture' : 'article';

        if ($tableName === 'fourniture') {
            $this->addSql('RENAME TABLE fourniture TO article');
        }

        $article = $schema->getTable($tableName);

        foreach ($article->getForeignKeys() as $fk) {
            if ($fk->getForeignTableName() === 'category' && $fk->getName() !== 'FK_23A0E6612469DE2') {
                $this->addSql(sprintf('ALTER TABLE article DROP FOREIGN KEY `%s`', $fk->getName()));

                foreach ($article->getIndexes() as $index) {
                    if ($index->getColumns() === ['category_id'] && $index->getName() !== 'IDX_23A0E6612469DE2') {
                        $this->addSql(sprintf('ALTER TABLE article RENAME INDEX `%s` TO IDX_23A0E6612469DE2', $index->getName()));
                    }
                }

                $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
            }
        }

        if ($article->hasColumn('is_active')) {
            $this->addSql('ALTER TABLE article DROP is_active');
        }

        foreach ($article->getIndexes() as $index) {
            if ($index->getColumns() === ['stock_quantity'] && $index->getName() !== 'idx_article_stock') {
                $this->addSql(sprintf('ALTER TABLE article RENAME INDEX `%s` TO idx_article_stock', $index->getName()));
            }
            if ($index->isUnique() && $index->getColumns() === ['reference'] && $index->getName() !== 'UNIQ_23A0E66AEA34913') {
                $this->addSql(sprintf('ALTER TABLE article RENAME INDEX `%s` TO UNIQ_23A0E66AEA34913', $index->getName()));
            }
        }

        $this->finishRename($schema, 'ligne_demande', 'FK_B90DE99C7294869C');
        $this->finishRename($schema, 'mouvement_stock', 'FK_61E2C8EB7294869C');
    }

    private function finishRename(Schema $schema, string $tableName, string $newFkName): void
    {
        $table = $schema->getTable($tableName);

        if (!$table->hasColumn('fourniture_id')) {
            return;
        }

        foreach ($table->getForeignKeys() as $fk) {
            if (in_array('fourniture_id', $fk->getLocalColumns(), true)) {
                $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY `%s`', $tableName, $fk->getName()));
            }
        }

        $this->addSql(sprintf('ALTER TABLE %s CHANGE fourniture_id article_id INT NOT NULL', $tableName));
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (article_id) REFERENCES article (id)', $tableName, $newFkName));
    }

    public function down(Schema $schema): void
    {
        throw new \RuntimeException('Migration corrective non réversible automatiquement — restaurer depuis une sauvegarde si besoin.');
    }
}
