<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221114075437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classe ADD professeur_id INT NOT NULL');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF96BAB22EE9 FOREIGN KEY (professeur_id) REFERENCES professeur (id)');
        $this->addSql('CREATE INDEX IDX_8F87BF96BAB22EE9 ON classe (professeur_id)');
        $this->addSql('ALTER TABLE eleve ADD professeur_id INT NOT NULL');
        $this->addSql('ALTER TABLE eleve ADD CONSTRAINT FK_ECA105F7BAB22EE9 FOREIGN KEY (professeur_id) REFERENCES professeur (id)');
        $this->addSql('CREATE INDEX IDX_ECA105F7BAB22EE9 ON eleve (professeur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF96BAB22EE9');
        $this->addSql('DROP INDEX IDX_8F87BF96BAB22EE9 ON classe');
        $this->addSql('ALTER TABLE classe DROP professeur_id');
        $this->addSql('ALTER TABLE eleve DROP FOREIGN KEY FK_ECA105F7BAB22EE9');
        $this->addSql('DROP INDEX IDX_ECA105F7BAB22EE9 ON eleve');
        $this->addSql('ALTER TABLE eleve DROP professeur_id');
    }
}
