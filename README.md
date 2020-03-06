Files Array Organizer
=====================

| [Getting Started](#getting-started) | [Explanation](#explanation) | [Examples](#examples) |
| ----------------------------------- | --------------------------- | --------------------- |

Dealing with the `$_FILES` array in PHP sucks. Most solutions only work for a
specific situation and aren't easily portable. This library is designed to organize any
possible incarnation of the `$_FILES` array into the structure you would intuitively expect. Getting data about uploaded files should be just as easy as reading the `$_POST` array.

Getting Started
---------------

### Requirements
PHP >= 7.0

### Installation
```bash
composer require tvanc/files-array-organizer
```

Explanation
-----------
For some situations, dealing with the `$_FILES` superglobal is fine. Uploading a single file via a field named `attachment` would generate a simple `$_FILES` array like this:

```php
[
    'attachment' = [
        'name'     => 'filename.jpg',
        'type'     => 'image/jpeg',
        'tmp_name' => '/tmp/phpR4nD0m',
        'error'    => 0,
        'size'     => 2407,
    ]
]
```

Getting the file's attributes is easy. The path to the input's value corresponds to the name of the input field.

```php
$file_name = $_FILES['attachment']['name'];
$file_size = $_FILES['attachment']['size'];
```

What about a field named `todo[0][attachments][]`, which accepts multiple files? Working with the files from this field should be easy. Right?
```php
<?php
$attachments = $_FILES['todo'][0]['attachments'];

foreach ($attachments as $attachment) {
    doSomething($attachment);
}
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="todo[0][attachments][]" multiple>
</form>
```

**Wrong.**

You thought the `$_FILES` array would look like this.
```php
[
    'todo' => [
        0 => [
            'attachments' => [
                0 => [
                    'name'     => 'filename.jpg',
                    'type'     => 'image/jpeg',
                    'tmp_name' => '/tmp/phpR4nD0m',
                    'error'    => 0,
                    'size'     => 2407,
                ],
                // ...
            ],
        ],
    ]
]
```

It will actually look like this:
```php
[
    'todo' => [
       'name'     => [
           0 => [
               'attachments' => [
                   0 => 'filename.jpg'
               ]
           ]
       ],
       'type'     => [
           0 => [
               'attachments' => [
                   0 => 'image/jpeg'
               ]
           ]
       ],
       'tmp_name' => [
           0 => [
               'attachments' => [
                   0 => '/tmp/phpKYBy4z'
               ]
           ]
       ],
       'error'    => [
           0 => [
               'attachments' => [
                   0 => 0
               ]
           ]
       ],
       'size'     => [
           0 => [
               'attachments' => [
                   0 => 2407
               ]
           ]
       ]
    ]
]
```

I leave it as an exercise for the reader to figure out how to extract the useful information out of that. If you don't want to repeat that exercise every time you need to handle more than one file at a time, just use this library.

To see this 

Examples
--------

### One input, one file

In a case like this, the `$_FILES` array is pretty straight forward and you the organized version is going to be identical to the unorganized version.

```php
<?php
use tvanc\FilesArrayOrganizer\FilesArrayOrganizer;

if ($_FILES) {
    $organizedFiles = FilesArrayOrganizer::organize($_FILES);

    if ($organizedFiles === $_FILES) {
        echo "Looks like you didn't really need to do that.";
    }
}
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="file">
    <button>Submit</button>
</form>
```

### A single input that accepts multiple files
```php
<?php
use tvanc\FilesArrayOrganizer\FilesArrayOrganizer;

if ($_FILES) {
    $organizedFiles = FilesArrayOrganizer::organize($_FILES);
    $attachments    = $organizedFiles['attachments'];
    
    foreach ($attachments as $attachment) {
        $attachment_name = $attachment['name'];
        $attachment_size = $attachment['size'];
    }
}
?>
<form method="post" enctype="multipart/form-data">
    <!-- Notice this input accepts multiple files -->
    <input type="file" name="attachments[]" multiple>
    <button>Submit</button>
</form>
```

### Multiple inputs that accept multiple files
```php
<?php
use tvanc\FilesArrayOrganizer\FilesArrayOrganizer;

// @var Todo[] $todos An array of todos
$todos = fetchTodos();

if ($_FILES) {
    $organizedFiles = FilesArrayOrganizer::organize($_FILES);

    foreach ($organizedFiles as $index => $postedTodo) {
        $attachments = $organizedFiles['todo'][$index];

        saveAttachments($index, $attachments);
    }
}
?>
<form method="post" enctype="multipart/form-data">
    <?php foreach ($todos as $index => $todo) { ?>
        <label>Attachments for todo <?= $index ?></label>
        <input 
            type="file"
            name="todo[<?= $index ?>][attachments][]"
            multiple
        >
    <?php } ?>

    <button>Submit</button>
</form>
```

### Execute a custom callback on each file
```php
<?php
use tvanc\FilesArrayOrganizer\FilesArrayOrganizer;
use YourOwn\MadeUpNameSpace\UploadedFile;

// Receive $file by reference to mutate it. You can even replace it entirely.
$callback = function (array & $file) {
    $file = new UploadedFile($file);
}

$organizedFilesArray = FilesArrayOrganizer::organize($_FILES, $callback);

// $attachments will be an array of UploadedFile objects
$attachments = $organizedFilesArray['attachments'];
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="attachments[]" multiple>
    <button>Submit</button>
</form>
```
