<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230515100438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account_request (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, surname VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, job_title VARCHAR(255) NOT NULL, representant TINYINT(1) NOT NULL, subscribe_newsletter TINYINT(1) DEFAULT NULL, category VARCHAR(255) NOT NULL, activity VARCHAR(255) NOT NULL, nb_physical_stores VARCHAR(255) NOT NULL, website VARCHAR(255) NOT NULL, marketplace_name VARCHAR(255) DEFAULT NULL, nb_employes VARCHAR(255) NOT NULL, turnover VARCHAR(255) DEFAULT NULL, company_legal_name VARCHAR(255) NOT NULL, company_trade_name VARCHAR(255) NOT NULL, registration_number VARCHAR(255) NOT NULL, street_address VARCHAR(255) NOT NULL, complement_address VARCHAR(255) DEFAULT NULL, postc_code VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, need_to_be_synced TINYINT(1) NOT NULL, last_sync DATETIME DEFAULT NULL, magento_answer_id INT NOT NULL, store_id INT NOT NULL, form_code VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logs JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, company VARCHAR(255) NOT NULL, number VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, email VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, vat_number VARCHAR(255) DEFAULT NULL, customer_price_group VARCHAR(255) NOT NULL, nav_salesperson VARCHAR(255) DEFAULT NULL, nav_payment_terms VARCHAR(255) DEFAULT NULL, nav_shipment_method VARCHAR(255) DEFAULT NULL, nav_payment_method VARCHAR(255) DEFAULT NULL, nav_allow_line_discount TINYINT(1) NOT NULL, nav_prices_including_vat TINYINT(1) DEFAULT NULL, nav_invoice_discount_code VARCHAR(255) DEFAULT NULL, billing_address_street VARCHAR(255) DEFAULT NULL, billing_address_street2 VARCHAR(255) DEFAULT NULL, billing_address_city VARCHAR(255) DEFAULT NULL, billing_address_state VARCHAR(255) DEFAULT NULL, billing_address_country VARCHAR(255) DEFAULT NULL, billing_address_postal_code VARCHAR(255) DEFAULT NULL, need_to_be_sync TINYINT(1) DEFAULT NULL, last_sync DATETIME DEFAULT NULL, magento_customer_id INT DEFAULT NULL, vat_group VARCHAR(255) DEFAULT NULL, website VARCHAR(255) NOT NULL, website_id INT NOT NULL, store VARCHAR(255) NOT NULL, store_id INT NOT NULL, phone_number VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logs JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE magento_request (id INT AUTO_INCREMENT NOT NULL, entity_name VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, start_sync DATETIME DEFAULT NULL, end_sync DATETIME DEFAULT NULL, status INT NOT NULL, end_point VARCHAR(255) NOT NULL, request JSON DEFAULT NULL, response JSON DEFAULT NULL, time_execution INT DEFAULT NULL, method VARCHAR(255) NOT NULL, error LONGTEXT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, magento_product_id INT NOT NULL, enabled TINYINT(1) NOT NULL, sku VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, item_discount_group VARCHAR(255) DEFAULT NULL, last_sync DATETIME DEFAULT NULL, need_to_be_sync TINYINT(1) NOT NULL, vat_group VARCHAR(255) NOT NULL, ecotax DOUBLE PRECISION DEFAULT NULL, canon_digital DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logs JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE account_request');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE magento_request');
        $this->addSql('DROP TABLE product');
    }
}
