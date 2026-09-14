<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\migrations;

use Craft;
use craft\db\Migration;

/**
 * Contact Form Extensions Install Migration.
 *
 * @author Hybrid Interactive
 *
 * @since 5.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return bool return a false value to indicate the migration fails
     *              and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return bool return a false value to indicate the migration fails
     *              and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin.
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        // contactform_submissions table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%contactform_submissions}}');
        if ($tableSchema == null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%contactform_submissions}}',
                [
                    'id'          => $this->integer()->notNull(),
                    'form'        => $this->string()->null(),
                    'subject'     => $this->string()->null(),
                    'fromName'    => $this->string()->null(),
                    'fromEmail'   => $this->string()->null(),
                    'message'     => $this->text()->notNull(),
                    'isSpam'      => $this->boolean()->defaultValue(false)->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid'         => $this->uid(),
                    'PRIMARY KEY(id)',
                ]
            );

            $this->createIndexIfMissing('{{%contactform_submissions}}', ['form'], false);
            $this->createIndexIfMissing('{{%contactform_submissions}}', ['dateCreated'], false);
            $this->createIndexIfMissing('{{%contactform_submissions}}', ['fromEmail'], false);
            $this->createIndexIfMissing('{{%contactform_submissions}}', ['isSpam'], false);
        }

        return $tablesCreated;
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin.
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        // contactform_submissions table
        $this->addForeignKey(
            null,
            '{{%contactform_submissions}}',
            'id',
            '{{%elements}}',
            'id',
            'CASCADE',
            null
        );
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin.
     *
     * @return void
     */
    protected function removeTables()
    {
        // contactform_submissions table
        $this->dropTableIfExists('{{%contactform_submissions}}');
    }
}
