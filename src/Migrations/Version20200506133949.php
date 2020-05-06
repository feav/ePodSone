<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200506133949 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, panier_id INT DEFAULT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, total_price DOUBLE PRECISION NOT NULL, INDEX IDX_6EEAA67D4584665A (product_id), INDEX IDX_6EEAA67DF77D927C (panier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE config (id INT AUTO_INCREMENT NOT NULL, mkey VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE coupon (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, type_reduction INT NOT NULL, price_reduction DOUBLE PRECISION NOT NULL, start DATE NOT NULL, end DATE NOT NULL, max_usage INT NOT NULL, current_usage INT NOT NULL, code VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formule (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(500) DEFAULT NULL, price DOUBLE PRECISION NOT NULL, month INT NOT NULL, name VARCHAR(255) NOT NULL, price_shipping DOUBLE PRECISION NOT NULL, try_days INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, total_price DOUBLE PRECISION NOT NULL, emmission DATE NOT NULL, price_shipping DOUBLE PRECISION NOT NULL, token VARCHAR(255) DEFAULT NULL, status INT NOT NULL, paiement_date DATE DEFAULT NULL, total_reduction DOUBLE PRECISION NOT NULL, INDEX IDX_24CC0DF2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE panier_coupon (panier_id INT NOT NULL, coupon_id INT NOT NULL, INDEX IDX_D146DD1CF77D927C (panier_id), INDEX IDX_D146DD1C66C5951B (coupon_id), PRIMARY KEY(panier_id, coupon_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE panier_coupon ADD CONSTRAINT FK_D146DD1CF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_coupon ADD CONSTRAINT FK_D146DD1C66C5951B FOREIGN KEY (coupon_id) REFERENCES coupon (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE abonnement_subscription');
        $this->addSql('ALTER TABLE abonnement ADD formule_id INT DEFAULT NULL, ADD panier_id INT DEFAULT NULL, ADD user_id INT DEFAULT NULL, ADD start DATETIME NOT NULL, ADD end DATETIME NOT NULL, ADD state INT NOT NULL, ADD is_paid INT NOT NULL, DROP name, DROP description, DROP price, DROP trial_day, DROP duree');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT FK_351268BB2A68F4D1 FOREIGN KEY (formule_id) REFERENCES formule (id)');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT FK_351268BBF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id)');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT FK_351268BBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_351268BB2A68F4D1 ON abonnement (formule_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_351268BBF77D927C ON abonnement (panier_id)');
        $this->addSql('CREATE INDEX IDX_351268BBA76ED395 ON abonnement (user_id)');
        $this->addSql('ALTER TABLE product CHANGE description description VARCHAR(5000) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE enabled enabled TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE panier_coupon DROP FOREIGN KEY FK_D146DD1C66C5951B');
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BB2A68F4D1');
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BBF77D927C');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DF77D927C');
        $this->addSql('ALTER TABLE panier_coupon DROP FOREIGN KEY FK_D146DD1CF77D927C');
        $this->addSql('CREATE TABLE abonnement_subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, abonnement_id INT DEFAULT NULL, date_sub DATETIME NOT NULL, date_expire DATETIME NOT NULL, active TINYINT(1) NOT NULL, is_resiliate TINYINT(1) NOT NULL, date_paid DATETIME DEFAULT NULL, is_paid TINYINT(1) NOT NULL, amount DOUBLE PRECISION NOT NULL, INDEX IDX_EF595F61A76ED395 (user_id), INDEX IDX_EF595F61F1D74413 (abonnement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE abonnement_subscription ADD CONSTRAINT FK_EF595F61A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE abonnement_subscription ADD CONSTRAINT FK_EF595F61F1D74413 FOREIGN KEY (abonnement_id) REFERENCES abonnement (id)');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE config');
        $this->addSql('DROP TABLE coupon');
        $this->addSql('DROP TABLE formule');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE panier_coupon');
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BBA76ED395');
        $this->addSql('DROP INDEX IDX_351268BB2A68F4D1 ON abonnement');
        $this->addSql('DROP INDEX UNIQ_351268BBF77D927C ON abonnement');
        $this->addSql('DROP INDEX IDX_351268BBA76ED395 ON abonnement');
        $this->addSql('ALTER TABLE abonnement ADD name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD price DOUBLE PRECISION NOT NULL, ADD trial_day INT NOT NULL, ADD duree INT NOT NULL, DROP formule_id, DROP panier_id, DROP user_id, DROP start, DROP end, DROP state, DROP is_paid');
        $this->addSql('ALTER TABLE product CHANGE description description VARCHAR(1000) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE enabled enabled TINYINT(1) DEFAULT NULL');
    }
}
