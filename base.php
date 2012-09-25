<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="{{ 'assets/default.css' | asset_url }}" />
    {% for stylesheet in stylesheets %}
        <link rel="stylesheet" type="text/css" href="{{ 'assets/' | asset_url }}{{ stylesheet }}.css" />
    {% endfor %}
    <title></title>
</head>
<body>
<div>
    {{ body }}
</div>
</body>
</html>