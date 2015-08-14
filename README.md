## Current status of the query builder

The query-builder is finished, in the future we will consider the integration of OrientDB
Graph Edition: http://code.google.com/p/orient/wiki/GraphEdTutorial.

To take advantage of the QB you only have to instantiate a Query object:

```
use Doctrine\OrientDB\Query\Query;

$query = new Query();
$query->from(array('users'))->where('username = ?', "admin");

echo $query->getRaw();      // SELECT FROM users WHERE username = "admin"
```

The Query object incapsulates lots of sub-commands, like SELECT, DROP, GRANT, INSERT
and so on...

You can use also those commands:

```
use Doctrine\OrientDB\Query\Command\Select;

$select = new Select(array('users'));
echo $select->getRaw();     // SELECT FROM users
```

However, we strongly discourage this approach: commands will change, Query, thought as a facade, - hopefully - not.