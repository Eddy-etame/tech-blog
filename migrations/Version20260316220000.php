<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260316220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create post_alert table for author subscription notifications';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('post_alert');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer');
        $table->addColumn('author_id', 'integer');
        $table->addColumn('created_at', 'datetime_immutable');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id']);
        $table->addIndex(['author_id']);
        $table->addUniqueIndex(['user_id', 'author_id'], 'user_author_unique');
        $table->addForeignKeyConstraint('user', ['user_id'], ['id']);
        $table->addForeignKeyConstraint('author', ['author_id'], ['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('post_alert');
    }
}
