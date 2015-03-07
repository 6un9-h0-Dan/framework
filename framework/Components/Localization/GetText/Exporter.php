<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\Localization\GetText;

use Spiral\Components\Localization\Exporter as LocalicationExporter;
use Spiral\Components\Localization\LocalizationException;

class Exporter extends LocalicationExporter
{
    /**
     * Escape string to validly insert it into PO file.
     *
     * @param string $string
     * @return string
     */
    static protected function escape($string)
    {
        return '"' . addcslashes(preg_replace('/[\n\r]+/', '\n', $string), '"') . '"';
    }

    /**
     * Export collected bundles data to specified file using format described by exporter. Language bundles will be exported
     * using PO format (same format used for GetText), due spiral uses localization bundles every translation line will be
     * prepended with comment contains bundle id, while editing PO file, comments should be left untouched otherwise corrupted
     * bundles will be created. You can use any existed program to edit PO file.
     *
     * @link http://en.wikipedia.org/wiki/Gettext
     * @param string $filename
     * @return mixed
     * @throws LocalizationException
     */
    public function exportBundles($filename)
    {
        if (!$this->language)
        {
            throw new LocalizationException("No language specified to be exported.");
        }

        /**
         * We can reuse plural formula to let PO editor understand our phrases.
         */
        $pluralForms = $this->i18nConfig['languages'][$this->language]['pluralizer']['countForms'];

        $formula = preg_replace('/\$form\[([0-9])\]/', '\1', $this->i18nConfig['languages'][$this->language]['pluralizer']['formula']);
        $pluralFormula = str_replace('$number', 'n', $formula);

        /**
         * PO file header.
         */
        $duplicates = array();
        $output = array();
        $output[] = 'msgid ""';
        $output[] = 'msgstr ""';
        $output[] = '"Project-Id-Version: Spiral Framework\n"';
        $output[] = '"Language-Id: ' . $this->language . '\n"';
        $output[] = '"Language: ' . $this->language . '\n"';
        $output[] = '"MIME-Version: 1.0\n"';
        $output[] = '"Content-Type: text/plain; charset=UTF-8\n"';
        $output[] = '"Content-Type: text/plain; charset=iso-8859-1\n"';
        $output[] = '"Plural-Forms: nplurals=' . $pluralForms . '; plural=(' . $pluralFormula . ')\n"';
        $output[] = '';

        /**
         * This is not default gettext syntax, but as spiral supports multiple namespaces for identifiers and gettext not.
         * There is simple hack: if identifier duplicated additional space symbol added at the end of it the string
         * (4 dups => 3 spaces), translator can see comment to understand where he can find this line. Extra spaces will
         * be removed during import.
         */
        foreach ($this->bundles as $bundle => $data)
        {
            foreach ($data as $line => $value)
            {
                if (isset($duplicates[$line]))
                {
                    $duplicates[$line]++;

                    //Nobody will see space at right :) and we will remove this space on importing
                    $line = $line . str_repeat(' ', $duplicates[$line] - 1);
                }
                else
                {
                    $duplicates[$line] = 1;
                }

                //Bundle and message ID
                $output[] = '# ' . $bundle;
                $output[] = '#: ' . $bundle;
                $output[] = 'msgid "' . addcslashes($line, '"') . '"';

                if (is_array($value))
                {
                    //Plural forms
                    $output[] = 'msgid_plural ' . self::escape($value[count($value) - 1]);

                    for ($form = 0; $form < $pluralForms; $form++)
                    {
                        if (isset($value[$form]))
                        {
                            $output[] = 'msgstr[' . $form . '] ' . self::escape($value[$form]);
                        }
                        else
                        {
                            $output[] = 'msgstr[' . $form . '] ' . self::escape($value[count($value) - 1]);
                        }
                    }
                }
                else
                {
                    //We can escape text here, it will be backed to normal state on importing
                    $output[] = 'msgstr ' . self::escape($value);
                }

                $output[] = '';
            }
        }

        return $this->file->write($filename, join("\n", $output));
    }
}