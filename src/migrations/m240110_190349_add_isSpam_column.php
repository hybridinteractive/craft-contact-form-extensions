<?php

namespace hybridinteractive\contactformextensions\migrations;

use craft\db\Migration;

/**
 * m240110_190349_add_isSpam_column migration.
 */
class m240110_190349_add_isSpam_column extends Migration
{
    public const COLUMN_IS_SPAM = 'isSpam';

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn('{{%contactform_submissions}}', self::COLUMN_IS_SPAM, $this->boolean()->defaultValue(false));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn('{{%contactform_submissions}}', self::COLUMN_IS_SPAM);

        return false;
    }
}
