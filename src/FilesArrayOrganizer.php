<?php

declare(strict_types=1);

namespace tvanc\FilesArrayOrganizer;

/**
 * Creates an organized version of the messy and confusing $_FILES array.
 */
class FilesArrayOrganizer
{
    /**
     * Organize the <code>$_FILES</code> array the way it always should have
     * been.
     *
     * @param array    $filesArray
     * Required. An array structured like the <code>$_FILES</code> array.
     * Typically you'll just pass <code>$_FILES</code> directly, but receiving
     * the array as a parameter facilitates easy testing.
     *
     * For reference, submitting a form with these inputs:
     * <code>&lt;input type="file" name="attachment"></code>
     * <code>&lt;input type="file" name="line_item[0][attachments][]" multiple></code>
     *
     * Would produce a <code>$_FILES</code> array like this:
     * <pre>
     * // Would produce this $_FILES array
     * [
     *    'attachment' => [
     *        'name' => 'filename1.jpg'
     *        'type' => 'image/jpeg',
     *        'tmp_name' => '/tmp/phpR4nD0m'
     *        'error' => 0
     *        'size' => 2407
     *    ]
     *    'line_item' => [
     *       'name'     => [
     *           0 => [
     *               'attachments' => [
     *                   0 => 'filename2.jpg',
     *               ],
     *           ],
     *       ],
     *       'type'     => [
     *           0 => [
     *               'attachments' => [
     *                   0 => 'image/jpeg',
     *               ],
     *           ],
     *       ],
     *       'tmp_name' => [
     *           0 => [
     *               'attachments' => [
     *                   0 => '/tmp/phpKYBy4z',
     *               ],
     *           ],
     *       ],
     *       'error'    => [
     *           0 => [
     *               'attachments' => [
     *                   0 => 0,
     *               ],
     *           ],
     *       ],
     *       'size'     => [
     *           0 => [
     *               'attachments' => [
     *                   0 => 2407,
     *               ],
     *           ],
     *       ],
     *    ]
     * ]
     * </pre>
     *
     * @param callable $fileModifierCallback
     * Optional. A callback that receives the final representation of each file.
     * Use this callback to modify how individual files are represented in the
     * final array.
     *
     * @return array
     * The organized files array. Given a value for <code>$filesArray</code>
     * like that described above, this method would yield:
     * <pre>
     * [
     *    'attachment'         => [
     *        'name'     => 'filename.jpg',
     *        'type'     => 'image/jpeg',
     *        'tmp_name' => '/tmp/phpR4nD0m',
     *        'error'    => 0,
     *        'size'     => 2407,
     *    ],
     *    'line_item' => [
     *        0 => [
     *            'attachments' => [
     *                0 => [
     *                    'name'     => 'filename.jpg',
     *                    'type'     => 'image/jpeg',
     *                    'tmp_name' => '/tmp/phpKYBy4z',
     *                    'error'    => 0,
     *                    'size'     => 2407,
     *                ],
     *            ],
     *        ],
     *    ],
     * ]
     * </pre>
     */
    public static function organize(
        array $filesArray,
        callable $fileModifierCallback = null
    ) {
        $output              = [];
        $flattenedFilesArray = [];

        /* The keys of first level of the <code>$_FILES</code> array are the
         * top-level names of the <input type="file"> fields. Given a field
         * with the name line_item[0][attachments][], `line_item` would be one
         * of keys in the first level of <code>$_FILES</code>.
         */
        foreach ($filesArray as $top_level_name => $attributes) {
            $output[$top_level_name] = [];

            foreach ($attributes as $attribute_name => $attribute_values) {
                static::inner(
                    $output[$top_level_name],
                    $attribute_name,
                    $attribute_values,
                    $flattenedFilesArray
                );
            }
        }

        if ($fileModifierCallback) {
            array_walk($flattenedFilesArray, $fileModifierCallback);
        }

        return $output;
    }


    /**
     * To get the name of files uploaded via these upload fields:
     * <code>&lt;input type="file" name="attachment"></code>
     * <code>&lt;input type="file" name="line_item[0][attachments][]" multiple></code>
     *
     * You would need these paths:
     * <pre>
     * $_FILES['attachment']['name'];
     * $_FILES['line_item']['name'][0]['attachments'][0];
     * </pre>
     *
     * To figure out how to change that array to a form where we can
     * get the same information this way:
     * <pre>
     *    $organizedFiles['attachment'] ['name'];
     *    $organizedFiles['line_item'] [0]['attachments'][0] ['name'];
     * // ^ ----  $root_element  ---- ^ --- $infix_path --- ^ $attribute_name
     * </pre>
     *
     * Regardless of the structure of the original array, paths for the
     * resulting array will be [basename][...infix path...][attribute name].
     *
     * The start and end of the path will always be the same. This function
     * figures out what goes in between. In the first example, the infix path
     * is empty.
     *
     * @param array        $root_element
     * A root element of the file array. In the example documented above, this
     * is <code>$organizedFiles['attachment']</code> or
     * <code>$organizedFiles['line_item']</code>.
     *
     * @param string       $attribute_name
     * The name of the file attribute whose value we're currently ferreting out.
     * This will be one of <code>name</code>, <code>type</code>,
     * <code>tmp_name</code>, <code>error</code>, <code>size</code>.
     *
     * This is the final segment of the path we're trying to build within
     * {@see $root_element}.
     *
     * @param array|string $value
     * The value we're recursively exploring. Either another array that must be
     * recursed into, or - finally - the value for the file attribute,
     * <code>$attribute_name</code>.
     *
     * @param array        $files
     * Array, received by reference, that will contain the organized file information,
     * as a "flat" array of arrays of name-value pairs for the 5 available file
     * attributes.
     *
     * This allows a modifier callback to easily walk the organized files
     * without having to traverse a complicated output array.
     *
     * @param array        $path
     * The "path" to <code>$value</code> for the current recursion level.
     * With each stage of recursion, <code>$path</code> becomes one element
     * longer.
     */
    private static function inner(&$root_element, $attribute_name, $value, &$files = [], $path = [])
    {
        // If $value is not an array then we've arrived at the attribute value
        if (!is_array($value)) {
            $last_key = $attribute_name;
            $stage    = &$root_element;

            // With each element of $path, go one stage deeper into $root_element
            foreach ($path as $path_segment) {
                // If the stage doesn't exist yet, create it
                if (!isset($stage[$path_segment])) {
                    $stage[$path_segment] = [];
                }

                $stage = &$stage[$path_segment];
            }

            // Add the final stage to the $files array
            if (!in_array($stage, $files)) {
                $files[] = &$stage;
            }
            $stage[$last_key] = $value;

            return;
        }

        // If $value is an array, recurse into it, building up $infix_path
        foreach ($value as $child_field_index => $child_field_values) {
            // Create new array one path segment longer, without mutating $path
            $infix_path = array_merge($path, [$child_field_index]);

            static::inner(
                $root_element,
                $attribute_name,
                $child_field_values,
                $files,
                $infix_path
            );
        }
    }
}
