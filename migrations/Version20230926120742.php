<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230926120742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale_order ADD total_cost DOUBLE PRECISION DEFAULT NULL, ADD total_price DOUBLE PRECISION DEFAULT NULL, ADD tracking_url VARCHAR(255) DEFAULT NULL, ADD carrier VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sale_order_line ADD product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sale_order_line ADD CONSTRAINT FK_61B16AA54584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_61B16AA54584665A ON sale_order_line (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale_order DROP total_cost, DROP total_price, DROP tracking_url, DROP carrier');
        $this->addSql('ALTER TABLE sale_order_line DROP FOREIGN KEY FK_61B16AA54584665A');
        $this->addSql('DROP INDEX IDX_61B16AA54584665A ON sale_order_line');
        $this->addSql('ALTER TABLE sale_order_line DROP product_id');
    }
}
