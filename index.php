<?php
require_once  'vendor/autoload.php';

function display($data)
{
    $result = '<table>';
    foreach ($data as $line => $value) {
        $result .= '<tr>';
        $result .= '<td>';
        $result .= $line;
        $result .= '</td>';
        $result .= '<td>';
        $result .= is_array($value) && count($value[key($value)]) > 1 ?  display($value) : (is_array($value) ? implode('; ', $value) : $value);
        $result .= '</td>';
        $result .= '</tr>';
    }
    $result .= '</table>';
    return $result;
}


use Dehare\SCPHP\Request;
use Dehare\SCPHP\Connection;

Connection::setPort(9999);
Connection::connect();

?>
<!doctype html>
<html>
<head>
    <title>Squeezebox CLI PHP API</title>
</head>
<body>
    <h1>Squeezebox CLI PHP API</h1>
    <p>Showcase of basic functionality</p>
    <p>Development environment auto-connects to LMS docker container running on <strong>port 9999</strong></p>

    <h3>Server</h3>
    <table>
        <tr>
        <td>Server status</td>
        <td><?= display(Request::query('status')) ?></td>
        </tr>
    </table>

    <h3>Database</h3>
    <table>
        <tr>
            <td>Albums</td>
            <td><?php var_dump(Request::query('database:albums', [], [\Dehare\SCPHP\API::FLAG_FILL_KEYS => true])) ?></td>
        </tr>
        <tr>
            <td>Total albums</td>
            <td><?= Request::query('database:count:albums') ?></td>
        </tr>
        <tr>
            <td>Total songs</td>
            <td><?= Request::query('database:count:songs') ?></td>
        </tr>
        <tr>
            <td>List years</td>
            <td><?= var_dump(Request::query('database:years')) ?></td>
        </tr>
        <tr>
            <td>Media folder</td>
            <td><?php var_dump(Request::query('database:folder')) ?></td>
        </tr>
        <tr>
            <td>Tracks</td>
            <td><?php var_dump(Request::query('database:songs')) ?></td>
        </tr>
        <tr>
            <td>Search</td>
            <td><?php var_dump(Request::query('database:search', ['term' => 'li'])) ?></td>
        </tr>
    </table>
</body>
</html>