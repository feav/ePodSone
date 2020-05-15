<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200515064139 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE equipe_match DROP FOREIGN KEY FK_2057538F6D861B89');
        $this->addSql('ALTER TABLE equipe_match2 DROP FOREIGN KEY FK_67F277EF6D861B89');
        $this->addSql('ALTER TABLE equipe_match DROP FOREIGN KEY FK_2057538F2ABEACD6');
        $this->addSql('ALTER TABLE equipe_match2 DROP FOREIGN KEY FK_67F277EFAB434B30');
        $this->addSql('ALTER TABLE `match` DROP FOREIGN KEY FK_7A5BC5058A2D8B41');
        $this->addSql('ALTER TABLE match2 DROP FOREIGN KEY FK_6AC511478A2D8B41');
        $this->addSql('ALTER TABLE match2 DROP FOREIGN KEY FK_6AC51147C588DEF0');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA15F607770A');
        $this->addSql('ALTER TABLE `match` DROP FOREIGN KEY FK_7A5BC505F607770A');
        $this->addSql('ALTER TABLE match2 DROP FOREIGN KEY FK_6AC51147F607770A');
        $this->addSql('ALTER TABLE terrain DROP FOREIGN KEY FK_C87653B1F607770A');
        $this->addSql('ALTER TABLE terrain2 DROP FOREIGN KEY FK_A67B4B44F607770A');
        $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_18AFD9DFC54C8C93');
        $this->addSql('DROP TABLE equipe');
        $this->addSql('DROP TABLE equipe_match');
        $this->addSql('DROP TABLE equipe_match2');
        $this->addSql('DROP TABLE `match`');
        $this->addSql('DROP TABLE match2');
        $this->addSql('DROP TABLE terrain');
        $this->addSql('DROP TABLE terrain2');
        $this->addSql('DROP TABLE tournoi');
        $this->addSql('DROP TABLE type_tournoi');
        $this->addSql('ALTER TABLE commande ADD stripe_charge_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE equipe (id INT AUTO_INCREMENT NOT NULL, tournoi_id INT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, joueurs LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, en_competition TINYINT(1) NOT NULL, INDEX IDX_2449BA15F607770A (tournoi_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE equipe_match (equipe_id INT NOT NULL, match_id INT NOT NULL, INDEX IDX_2057538F2ABEACD6 (match_id), INDEX IDX_2057538F6D861B89 (equipe_id), PRIMARY KEY(equipe_id, match_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE equipe_match2 (equipe_id INT NOT NULL, match2_id INT NOT NULL, INDEX IDX_67F277EFAB434B30 (match2_id), INDEX IDX_67F277EF6D861B89 (equipe_id), PRIMARY KEY(equipe_id, match2_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE `match` (id INT AUTO_INCREMENT NOT NULL, terrain_id INT DEFAULT NULL, tournoi_id INT NOT NULL, etat VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, score VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, duree INT NOT NULL, date_debut DATETIME DEFAULT NULL, vainqueur INT DEFAULT NULL, num_tour INT NOT NULL, date_fin DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_7A5BC5058A2D8B41 (terrain_id), INDEX IDX_7A5BC505F607770A (tournoi_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE match2 (id INT AUTO_INCREMENT NOT NULL, tournoi_id INT NOT NULL, terrain_id INT DEFAULT NULL, terrain2_id INT DEFAULT NULL, etat VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, score VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, duree INT NOT NULL, date_debut DATETIME DEFAULT NULL, vainqueur INT DEFAULT NULL, num_tour INT NOT NULL, date_fin DATETIME DEFAULT NULL, INDEX IDX_6AC51147C588DEF0 (terrain2_id), UNIQUE INDEX UNIQ_6AC511478A2D8B41 (terrain_id), INDEX IDX_6AC51147F607770A (tournoi_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE terrain (id INT AUTO_INCREMENT NOT NULL, tournoi_id INT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, occupe TINYINT(1) NOT NULL, INDEX IDX_C87653B1F607770A (tournoi_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE terrain2 (id INT AUTO_INCREMENT NOT NULL, tournoi_id INT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, occupe TINYINT(1) NOT NULL, INDEX IDX_A67B4B44F607770A (tournoi_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tournoi (id INT AUTO_INCREMENT NOT NULL, type_id INT DEFAULT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, nbr_equipe INT NOT NULL, nbr_terrain INT NOT NULL, duree INT NOT NULL, date_create DATETIME NOT NULL, nbr_tour INT NOT NULL, current_tour INT NOT NULL, date_debut DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, refresh TINYINT(1) DEFAULT NULL, etat VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, nbr_joueur INT NOT NULL, logo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, nbr_joueur_equipe INT NOT NULL, INDEX IDX_18AFD9DFC54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE type_tournoi (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, referent VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA15F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_match ADD CONSTRAINT FK_2057538F2ABEACD6 FOREIGN KEY (match_id) REFERENCES `match` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_match ADD CONSTRAINT FK_2057538F6D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_match2 ADD CONSTRAINT FK_67F277EF6D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_match2 ADD CONSTRAINT FK_67F277EFAB434B30 FOREIGN KEY (match2_id) REFERENCES match2 (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `match` ADD CONSTRAINT FK_7A5BC5058A2D8B41 FOREIGN KEY (terrain_id) REFERENCES terrain (id)');
        $this->addSql('ALTER TABLE `match` ADD CONSTRAINT FK_7A5BC505F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match2 ADD CONSTRAINT FK_6AC511478A2D8B41 FOREIGN KEY (terrain_id) REFERENCES terrain (id)');
        $this->addSql('ALTER TABLE match2 ADD CONSTRAINT FK_6AC51147C588DEF0 FOREIGN KEY (terrain2_id) REFERENCES terrain2 (id)');
        $this->addSql('ALTER TABLE match2 ADD CONSTRAINT FK_6AC51147F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE terrain ADD CONSTRAINT FK_C87653B1F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE terrain2 ADD CONSTRAINT FK_A67B4B44F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_18AFD9DFC54C8C93 FOREIGN KEY (type_id) REFERENCES type_tournoi (id)');
        $this->addSql('ALTER TABLE commande DROP stripe_charge_id');
    }
}
