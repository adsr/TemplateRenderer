<!DOCTYPE html>
<html>
<head>
<title><? $t->render_block('title', $t->er('Default title')); ?> &mdash; TemplateRenderer</title>
</head>
<body>
<p>TemplateRenderer example</p>
<? $t->render_block('content'); ?>
</body>
</html>
