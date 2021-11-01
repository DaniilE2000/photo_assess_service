<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = $name
?>

<?php

echo '<pre>';
print_r($message);
echo '<pre></pre>';
print_r($exception);
echo '<pre>';
?>