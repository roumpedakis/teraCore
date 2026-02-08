<?php

namespace Tests\Unit;

use TestCase;
use App\Core\Translator;
use App\Core\Database;

class TranslatorTest extends TestCase
{
    protected $translator;
    protected $db;

    public function test_initialize_translator() {
        $db = Database::getInstance();
        $translator = new Translator($db);
        assert_true($translator instanceof Translator);
    }

    public function test_save_translation() {
        $db = Database::getInstance();
        $translator = new Translator($db);
        
        // Ensure articles table exists with proper schema
        try {
            // Drop if exists to reset
            $db->getConnection()->exec("DROP TABLE IF EXISTS article_descriptions");
            $db->getConnection()->exec("DROP TABLE IF EXISTS articles");
        } catch (\Exception $e) {
            // ignore
        }
        
        try {
            $db->getConnection()->exec("
                CREATE TABLE articles (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Insert test article
            $db->getConnection()->exec("INSERT INTO articles (id, title) VALUES (1, 'Test Article')");
        } catch (\Exception $e) {
            // Table setup error
        }

        $result = $translator->saveTranslation(
            'article',
            1,
            'description',
            'el',
            'Ελληνική Περιγραφή'
        );
        assert_true($result);
    }

    public function test_get_translation() {
        $db = Database::getInstance();
        $translator = new Translator($db);
        
        $translation = $translator->getTranslation(
            'article',
            1,
            'description',
            'el'
        );
        assert_equal('Ελληνική Περιγραφή', $translation);
    }

    public function test_save_multiple_language_translations() {
        $db = Database::getInstance();
        $translator = new Translator($db);
        
        $translator->saveTranslation(
            'article',
            1,
            'description',
            'en',
            'English Description'
        );

        $translator->saveTranslation(
            'article',
            1,
            'description',
            'de',
            'Deutsche Beschreibung'
        );

        assert_true(true);
    }

    public function test_get_all_translations_for_field() {
        $db = Database::getInstance();
        $translator = new Translator($db);
        
        $translations = $translator->getAllTranslations(
            'article',
            1,
            'description'
        );

        assert_array_key_exists('el', $translations);
        assert_array_key_exists('en', $translations);
        assert_array_key_exists('de', $translations);
        assert_equal('Ελληνική Περιγραφή', $translations['el']);
    }

    public function test_get_nonexistent_translation_returns_null() {
        $db = Database::getInstance();
        $translator = new Translator($db);
        
        $translation = $translator->getTranslation(
            'article',
            999,
            'description',
            'el'
        );
        assert_equal(null, $translation);
    }

    public function test_update_existing_translation() {
        $db = Database::getInstance();
        $translator = new Translator($db);
        
        $translator->saveTranslation(
            'article',
            1,
            'description',
            'el',
            'Ενημερωμένη Περιγραφή'
        );

        $translation = $translator->getTranslation(
            'article',
            1,
            'description',
            'el'
        );
        assert_equal('Ενημερωμένη Περιγραφή', $translation);
    }

    public function test_delete_entity_translations() {
        $db = Database::getInstance();
        $translator = new Translator($db);
        
        $translator->deleteEntityTranslations('article', 1, 'description');

        $translation = $translator->getTranslation(
            'article',
            1,
            'description',
            'el'
        );
        assert_equal(null, $translation);
    }

    public function test_save_and_get_title_translations() {
        $db = Database::getInstance();
        $translator = new Translator($db);
        
        // Ensure article 2 exists
        try {
            $stmt = $db->getConnection()->prepare("SELECT id FROM articles WHERE id = 2");
            $stmt->execute();
            if (!$stmt->fetch()) {
                $db->getConnection()->exec("INSERT INTO articles (id, title) VALUES (2, 'Article 2')");
            }
        } catch (\Exception $e) {
            // ignore
        }
        
        // Test a different field
        $translator->saveTranslation(
            'article',
            2,
            'title',
            'el',
            'Τίτλος Άρθρου'
        );

        $translation = $translator->getTranslation(
            'article',
            2,
            'title',
            'el'
        );
        assert_equal('Τίτλος Άρθρου', $translation);
    }
}

require_once __DIR__ . '/../bootstrap.php';
$test = new TranslatorTest();
$test->run();

