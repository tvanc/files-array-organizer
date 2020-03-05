# Files Array Organizer

Dealing with the `$_FILES` array in PHP sucks. Most solutions only work for a
specific situation and aren't easily portable. This library is intended to be
a simple, general solution to organize any possible incarnation of the `$_FILES`
array into the structure you would intuitively expect.


| [Getting Started](#getting-started) | [Explanation](#explanation) | [Examples](#examples) |
| ----------------------------------- | --------------------------- | --------------------- |

## Getting Started

### Requirements
PHP >= 7.0

### Installation
```bash
composer require tvanc\files-array-organizer
```

## Explanation
The structure of the `$_FILES` array can be quite surprising. 
 
```php
<?php
// upload.php
var_dump($_FILES);
?>
<form>
<input type="file" name="attachment">
<input type="file" name="line_item[0][attachments][]" multiple>
<button>Submit</button>
</form>
```

A file uploaded via the field named `attachment` would be accessible at `$_FILES['attachment']`. 
To get the name of the file you might write `$_FILES['attachment']['name']`. 
To get the size, easy: `$_FILES['attachment']['size']`.

What about the field named `line_item[0][attachments][]`? 
How would you get the name of the _first_ file uploaded through that field? 
(Notice this field takes multiple files.)

You might expect `$_FILES['line_item'][0][attachments][0]['name']` to work.

But you'd be wrong.
The actual expression would be `$_FILES['line_item']['name'][0]['attachments'][0]`.


