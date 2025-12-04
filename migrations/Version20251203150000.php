<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter la colonne 'stock' à la table produit
 *
 * PRINCIPE :
 * - up() = Ajoute la colonne stock (exécuté lors de php bin/console doctrine:migrations:migrate)
 * - down() = Supprime la colonne stock (pour annuler la migration)
 */
final class Version20251203150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter la colonne stock à la table produit pour gérer les quantités disponibles';
    }

    // Méthode exécutée pour appliquer la migration
    public function up(Schema $schema): void
    {
        // Ajouter la colonne 'stock' de type INT (nombre entier)
        // NOT NULL = obligatoire
        // DEFAULT 0 = valeur par défaut à 0 si aucune valeur n'est fournie
        $this->addSql('ALTER TABLE produit ADD COLUMN stock INT NOT NULL DEFAULT 0');
    }

    // Méthode exécutée pour annuler la migration
    public function down(Schema $schema): void
    {
        // Supprimer la colonne 'stock'
        $this->addSql('ALTER TABLE produit DROP COLUMN stock');
    }
}
