<?php

declare(strict_types=1);

namespace tvanc\FilesArrayOrganizer\Test;

use PHPUnit\Framework\TestCase;
use tvanc\FilesArrayOrganizer\FilesArrayOrganizer;

final class FilesArrayOrganizerTest extends TestCase
{
    public function testDefaultOutput()
    {
        $input                 = $this->getInputFilesArray();
        $expectedDefaultOutput = $this->getExpectedDefaultResult();
        $actualDefaultOutput   = FilesArrayOrganizer::organize($input);

        $this->assertEquals(
            $expectedDefaultOutput,
            $actualDefaultOutput,
            'Files are organized exactly as expected'
        );
    }


    public function testCustomCallback()
    {
        $input   = $this->getInputFilesArray();
        $mutator = function (array &$file) {
            // Do some simple mutation we can verify later
            $file = [10, $file];
        };

        $defaultOutput      = FilesArrayOrganizer::organize($input);
        $defaultShallowFile = $defaultOutput['attachment'];
        $defaultDeepFile    = $defaultOutput['line_item'][0]['attachments'][0];

        $customOutput      = FilesArrayOrganizer::organize($input, $mutator);
        $customShallowFile = $customOutput['attachment'];
        $customDeepFile    = $customOutput['line_item'][0]['attachments'][0];

        $this->assertNotEquals(
            $defaultShallowFile,
            $customShallowFile,
            "Default and custom shallowly nested files differ"
        );

        $this->assertNotEquals(
            $defaultDeepFile,
            $customDeepFile,
            "Default and custom deeply nested files differ"
        );

        $mutatedDefaultShallowFile = $this->getMutatedCopy($defaultShallowFile, $mutator);
        $mutatedDefaultDeepFile    = $this->getMutatedCopy($defaultDeepFile, $mutator);

        $this->assertEquals(
            $mutatedDefaultDeepFile,
            [10, $defaultDeepFile],
            "Test mutator is working properly"
        );

        $this->assertEquals($customShallowFile, $mutatedDefaultShallowFile, "Shallow file was mutated as expected");
        $this->assertEquals($customDeepFile, $mutatedDefaultDeepFile, "Deeply nested file was mutated as expected");

        $expectedCustomResult = [
            'attachment' => $mutatedDefaultShallowFile,
            'line_item'  => [0 => ['attachments' => [$mutatedDefaultDeepFile]]]
        ];

        $this->assertEquals(
            $expectedCustomResult,
            $customOutput,
            "Custom output precisely matches expected structure and content"
        );
    }


    /**
     * @param array    $file
     * @param callable $mutatorCallback
     *
     * @return array
     * A copy of {@see $file} that's been altered by {@see $mutatorCallback}
     */
    private function getMutatedCopy(array $file, callable $mutatorCallback)
    {
        $mutatorCallback($file);

        return $file;
    }


    /**
     * @return array[]
     * The organized files array. This is exactly what we want to get when we
     * organize the return value of {@see getInputFilesArray()}
     */
    private function getExpectedDefaultResult()
    {
        return [
            'attachment' => [
                'name'     => 'filename1.jpg',
                'type'     => 'image/jpeg',
                'tmp_name' => '/tmp/phpR4nD0m',
                'error'    => 0,
                'size'     => 2407,
            ],
            'line_item'  => [
                0 => [
                    'attachments' => [
                        0 => [
                            'name'     => 'filename2.jpg',
                            'type'     => 'image/jpeg',
                            'tmp_name' => '/tmp/phpKYBy4z',
                            'error'    => 0,
                            'size'     => 3802,
                        ],
                    ],
                ],
            ],
        ];
    }


    /**
     * @return array
     * An array representing what you would get after submitting a form
     * with these file fields:
     *
     * <pre>
     * &lt;input type="file" name="attachment">
     * &lt;input type="file" name="line_item[0][attachments][]" multiple>
     * </pre>
     */
    private function getInputFilesArray()
    {
        return [
            'attachment' => [
                'name'     => 'filename1.jpg',
                'type'     => 'image/jpeg',
                'tmp_name' => '/tmp/phpR4nD0m',
                'error'    => 0,
                'size'     => 2407,
            ],
            'line_item'  => [
                'name'     => [
                    0 => [
                        'attachments' => [
                            0 => 'filename2.jpg',
                        ],
                    ],
                ],
                'type'     => [
                    0 => [
                        'attachments' => [
                            0 => 'image/jpeg',
                        ],
                    ],
                ],
                'tmp_name' => [
                    0 => [
                        'attachments' => [
                            0 => '/tmp/phpKYBy4z',
                        ],
                    ],
                ],
                'error'    => [
                    0 => [
                        'attachments' => [
                            0 => 0,
                        ],
                    ],
                ],
                'size'     => [
                    0 => [
                        'attachments' => [
                            0 => 3802,
                        ],
                    ],
                ],
            ]
        ];
    }
}
