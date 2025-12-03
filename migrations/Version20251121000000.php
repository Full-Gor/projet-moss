<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter la colonne 'role' à la table user
 */
final class Version20251121000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter la colonne role à la table user pour gérer les rôles (user/admin)';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la colonne 'role' avec la valeur par défaut 'user'
        $this->addSql("ALTER TABLE user ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user'");
    }

    public function down(Schema $schema): void
    {
        // Supprimer la colonne 'role' si on annule la migration
        $this->addSql('ALTER TABLE user DROP COLUMN role');
    }
}
