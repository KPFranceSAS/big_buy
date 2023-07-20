<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230523151446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE web_order (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, magento_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', company VARCHAR(255) NOT NULL, customer_number VARCHAR(255) NOT NULL, status INT NOT NULL, magento_order_id INT NOT NULL, magento_order_nbr VARCHAR(255) DEFAULT NULL, store_id INT NOT NULL, website_id INT NOT NULL, store_code VARCHAR(255) NOT NULL, website_code VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, amount_total DOUBLE PRECISION NOT NULL, total_paid DOUBLE PRECISION NOT NULL, payment_method VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logs JSON DEFAULT NULL, INDEX IDX_383A97069395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE web_order_product (web_order_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_7FB273699AC07CD5 (web_order_id), INDEX IDX_7FB273694584665A (product_id), PRIMARY KEY(web_order_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE web_order ADD CONSTRAINT FK_383A97069395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE web_order_product ADD CONSTRAINT FK_7FB273699AC07CD5 FOREIGN KEY (web_order_id) REFERENCES web_order (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE web_order_product ADD CONSTRAINT FK_7FB273694584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE web_order DROP FOREIGN KEY FK_383A97069395C3F3');
        $this->addSql('ALTER TABLE web_order_product DROP FOREIGN KEY FK_7FB273699AC07CD5');
        $this->addSql('ALTER TABLE web_order_product DROP FOREIGN KEY FK_7FB273694584665A');
        $this->addSql('DROP TABLE web_order');
        $this->addSql('DROP TABLE web_order_product');
    }
}
