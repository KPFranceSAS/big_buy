<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230904131046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sale_order (id INT AUTO_INCREMENT NOT NULL, order_number VARCHAR(255) NOT NULL, release_date DATETIME NOT NULL, status INT NOT NULL, release_date_string VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logs JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sale_order_line (id INT AUTO_INCREMENT NOT NULL, sale_order_id INT NOT NULL, sku VARCHAR(255) NOT NULL, quantity INT NOT NULL, line_number INT DEFAULT NULL, big_buy_order_line VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logs JSON DEFAULT NULL, INDEX IDX_61B16AA593EB8192 (sale_order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sale_order_line ADD CONSTRAINT FK_61B16AA593EB8192 FOREIGN KEY (sale_order_id) REFERENCES sale_order (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale_order_line DROP FOREIGN KEY FK_61B16AA593EB8192');
        $this->addSql('DROP TABLE sale_order');
        $this->addSql('DROP TABLE sale_order_line');
    }
}
