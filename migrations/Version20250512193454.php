<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250512193454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE job_applications (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, date_naissance DATE NOT NULL, lieu_residence VARCHAR(255) NOT NULL, nationalite VARCHAR(100) NOT NULL, permis_travail TINYINT(1) NOT NULL, telephone VARCHAR(20) NOT NULL, email VARCHAR(255) NOT NULL, poste_souhaite VARCHAR(255) NOT NULL, raison_interet_poste LONGTEXT NOT NULL, cv_name VARCHAR(255) DEFAULT NULL, motivation_name VARCHAR(255) DEFAULT NULL, langues JSON NOT NULL, motivation_mma LONGTEXT NOT NULL, contribution_mma LONGTEXT NOT NULL, disponibilite VARCHAR(50) NOT NULL, engagement VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE job_applications
        SQL);
    }
}
