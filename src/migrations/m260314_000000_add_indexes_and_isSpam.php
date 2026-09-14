<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\migrations;

use craft\db\Migration;

/**
 * Adds indexes and isSpam column for submissions.
 *
 * @author Hybrid Interactive
 *
 * @since 5.1.0
 */
class m260314_000000_add_indexes_and_isSpam extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $table = '{{%contactform_submissions}}';

        if (!$this->db->columnExists($table, 'isSpam')) {
            $this->addColumn($table, 'isSpam', $this->boolean()->defaultValue(false)->notNull());
        }

        $this->createIndexIfMissing($table, ['form'], false);
        $this->createIndexIfMissing($table, ['dateCreated'], false);
        $this->createIndexIfMissing($table, ['fromEmail'], false);
        $this->createIndexIfMissing($table, ['isSpam'], false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $table = '{{%contactform_submissions}}';

        $this->dropIndexIfExists($table, ['form'], false);
        $this->dropIndexIfExists($table, ['dateCreated'], false);
        $this->dropIndexIfExists($table, ['fromEmail'], false);
        $this->dropIndexIfExists($table, ['isSpam'], false);

        if ($this->db->columnExists($table, 'isSpam')) {
            $this->dropColumn($table, 'isSpam');
        }

        return true;
    }
}
