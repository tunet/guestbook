<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230203184225 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment ADD state VARCHAR(255) DEFAULT \'submitted\' NOT NULL');
        $this->addSql("UPDATE comment SET state='published'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP state');
    }
}
