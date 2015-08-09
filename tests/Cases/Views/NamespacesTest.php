<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Tests\Cases\Components\View;

use Spiral\Core\Configurator;
use Spiral\Core\Container;
use Spiral\Files\FileManager;
use Spiral\Tests\TestCase;
use Spiral\Views\ViewManager;

class NamespacesTest extends TestCase
{
    public function testNamespaces()
    {
        $view = $this->viewManager();

        $this->assertSame('This is view A in default namespace A.', $view->render('view-a'));
        $this->assertSame('This is view B in default namespace B.', $view->render('view-b'));

        $this->assertSame('This is view A in default namespace A.',
            $view->render('default:view-a'));
        $this->assertSame('This is view B in default namespace B.',
            $view->render('default:view-b'));
        $this->assertSame('This is view A in custom namespace.', $view->render('namespace:view-a'));
    }

    protected function tearDown()
    {
        $file = new FileManager();
        foreach ($file->getFiles(directory('runtime')) as $filename) {
            $file->delete($filename);
        }
    }

    /**
     * @param array $config
     * @return ViewManager
     */
    protected function viewManager(array $config = [])
    {
        if (empty($config)) {
            $config = [
                'cache'        => [
                    'enabled'   => false,
                    'directory' => directory('runtime')
                ],
                'namespaces'   => [
                    'default'   => [
                        __DIR__ . '/fixtures/default/',
                        __DIR__ . '/fixtures/default-b/',
                    ],
                    'namespace' => [
                        __DIR__ . '/fixtures/namespace/',
                    ]
                ],
                'dependencies' => [],
                'engines'      => [
                    'default' => [
                        'extensions' => ['php'],
                        'compiler'   => 'Spiral\Views\Compiler',
                        'view'       => 'Spiral\Views\View'
                    ]
                ],
                'compiler'     => [
                    'processors' => [

                    ]
                ],
                'associations' => [

                ]
            ];
        }

        return new ViewManager(new Configurator($config), new Container(), new FileManager());
    }
}