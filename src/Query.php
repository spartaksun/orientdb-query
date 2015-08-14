<?php

/*
 * This file is part of the Doctrine\OrientDB package.
 *
 * (c) Alessandro Nadalin <alessandro.nadalin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Query class to build queries execute by an Doctrine\OrientDB's protocol adapter.
 *
 * @package    Doctrine\OrientDB
 * @subpackage Query
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 */

namespace Doctrine\OrientDB\Query;

use Doctrine\OrientDB\Query\Command\Delete;
use Doctrine\OrientDB\Query\Command\OClass\Alter;
use Doctrine\OrientDB\Query\Command\Reference\Find;
use Doctrine\OrientDB\Query\Command\Update\Add;
use Doctrine\OrientDB\Query\Validator\ValidationException;
use Doctrine\OrientDB\Query\Command\Credential\Grant;
use Doctrine\OrientDB\Query\Command\Credential\Revoke;
use Doctrine\OrientDB\Query\Command\Insert;
use Doctrine\OrientDB\Query\Validator\Rid as RidValidator;

class Query implements QueryInterface
{
    /**
     * @var QueryInterface
     */
    protected $command = null;
    protected $commands = array(
        'select'            => 'Doctrine\OrientDB\Query\Command\Select',
        'insert'            => 'Doctrine\OrientDB\Query\Command\Insert',
        'delete'            => 'Doctrine\OrientDB\Query\Command\Delete',
        'update'            => 'Doctrine\OrientDB\Query\Command\Update',
        'update.add'        => 'Doctrine\OrientDB\Query\Command\Update\Add',
        'update.remove'     => 'Doctrine\OrientDB\Query\Command\Update\Remove',
        'update.put'        => 'Doctrine\OrientDB\Query\Command\Update\Put',
        'grant'             => 'Doctrine\OrientDB\Query\Command\Credential\Grant',
        'revoke'            => 'Doctrine\OrientDB\Query\Command\Credential\Revoke',
        'class.create'      => 'Doctrine\OrientDB\Query\Command\OClass\Create',
        'class.drop'        => 'Doctrine\OrientDB\Query\Command\OClass\Drop',
        'class.alter'       => 'Doctrine\OrientDB\Query\Command\OClass\Alter',
        'truncate.class'    => 'Doctrine\OrientDB\Query\Command\Truncate\OClass',
        'truncate.cluster'  => 'Doctrine\OrientDB\Query\Command\Truncate\Cluster',
        'truncate.record'   => 'Doctrine\OrientDB\Query\Command\Truncate\Record',
        'references.find'   => 'Doctrine\OrientDB\Query\Command\Reference\Find',
        'property.create'   => 'Doctrine\OrientDB\Query\Command\Property\Create',
        'property.drop'     => 'Doctrine\OrientDB\Query\Command\Property\Drop',
        'property.alter'    => 'Doctrine\OrientDB\Query\Command\Property\Alter',
        'index.drop'        => 'Doctrine\OrientDB\Query\Command\Index\Drop',
        'index.create'      => 'Doctrine\OrientDB\Query\Command\Index\Create',
        'index.count'       => 'Doctrine\OrientDB\Query\Command\Index\Count',
        'index.put'         => 'Doctrine\OrientDB\Query\Command\Index\Put',
        'index.remove'      => 'Doctrine\OrientDB\Query\Command\Index\Remove',
        'index.lookup'      => 'Doctrine\OrientDB\Query\Command\Index\Lookup',
        'index.rebuild'     => 'Doctrine\OrientDB\Query\Command\Index\Rebuild',
        'link'              => 'Doctrine\OrientDB\Query\Command\Create\Link',
    );

    /**
     * {@inheritdoc}
     */
    public function __construct(array $target = array(), array $commands = array())
    {
        $this->setCommands($commands);

        $commandClass = $this->getCommandClass('select');
        $this->command = new $commandClass($target);
    }

    /**
     * {@inheritdoc}
     */
    public function add(array $updates, $class, $append = true)
    {
        $commandClass = $this->getCommandClass('update.add');
        $this->command = new $commandClass($updates, $class, $append);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function alter($class, $attribute, $value)
    {
        $commandClass = $this->getCommandClass('class.alter');
        $this->command = new $commandClass($class, $attribute, $value);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function alterProperty($class, $property, $attribute, $value)
    {
        $commandClass = $this->getCommandClass('property.alter');
        $this->command = new $commandClass($property);

        return $this->command->on($class)->changing($attribute, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function andWhere($condition, $value = null)
    {
        return $this->command->andwhere($condition, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function between($key, $left, $right)
    {
        return $this->command->between($key, $left, $right);
    }

    /**
     * {@inheritdoc}
     */
    public function create($class, $property = null, $type = null, $linked = null)
    {
        return $this->executeClassOrPropertyCommand(
            'create', $class, $property, $type, $linked
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete($from)
    {
        $commandClass = $this->getCommandClass('delete');
        $this->command = new $commandClass($from);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function drop($class, $property = null)
    {
        return $this->executeClassOrPropertyCommand('drop', $class, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function fields(array $fields, $append = true)
    {
        return $this->command->fields($fields, $append);
    }

    /**
     * {@inheritdoc}
     */
    public function from(array $target, $append = true)
    {
        return $this->command->from($target, $append);
    }

    /**
     * Returns the internal command.
     *
     * @return Command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function getRaw()
    {
        return $this->command->getRaw();
    }

    /**
     * {@inheritdoc}
     */
    public function getTokens()
    {
        return $this->command->getTokens();
    }

    /**
     * {@inheritdoc}
     */
    public function grant($permission)
    {
        $commandClass = $this->getCommandClass('grant');
        $this->command = new $commandClass($permission);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function findReferences($rid, array $classes = array(), $append = true)
    {
        $commandClass = $this->getCommandClass('references.find');
        $this->command = new $commandClass($rid);
        $this->command->in($classes, $append);

        return $this->command;
    }


    /**
     * {@inheritdoc}
     */
    public function in(array $in, $append = true)
    {
        return $this->command->in($in, $append);
    }

    /**
     * {@inheritdoc}
     */
    public function index($property, $type, $class = null)
    {
        $commandClass = $this->getCommandClass('index.create');
        $this->command = new $commandClass($property, $type, $class);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function indexCount($indexName)
    {
        $commandClass = $this->getCommandClass('index.count');
        $this->command = new $commandClass($indexName);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function indexPut($indexName, $key, $rid)
    {
        $commandClass = $this->getCommandClass('index.put');
        $this->command = new $commandClass($indexName, $key, $rid);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function indexRemove($indexName, $key, $rid = null)
    {
        $commandClass = $this->getCommandClass('index.remove');
        $this->command = new $commandClass($indexName, $key, $rid);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function rebuild($indexName)
    {
        $commandClass = $this->getCommandClass('index.rebuild');
        $this->command = new $commandClass($indexName);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function insert()
    {
        $commandClass = $this->getCommandClass('insert');
        $this->command = new $commandClass;

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function into($target)
    {
        return $this->command->into($target);
    }

    /**
     * {@inheritdoc}
     */
    public function limit($limit)
    {
        return $this->command->limit($limit);
    }

    /**
     * {@inheritdoc}
     */
    public function skip($records)
    {
        return $this->command->skip($records);
    }

    /**
     * {@inheritdoc}
     */
    public function link($class, $property, $alias, $inverse = false)
    {
        $commandClass = $this->getCommandClass('link');
        $this->command = new $commandClass($class, $property, $alias, $inverse);

        return $this->command;
    }

    public function lookup($indexName)
    {
        $commandClass = $this->getCommandClass('index.lookup');
        $this->command = new $commandClass($indexName);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function on($on)
    {
        return $this->command->on($on);
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($order, $append = true, $first = false)
    {
        return $this->command->orderBy($order, $append, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($condition, $value = null)
    {
        return $this->command->orWhere($condition, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(array $updates, $class, $append = true)
    {
        $commandClass = $this->getCommandClass('update.remove');
        $this->command = new $commandClass($updates, $class, $append);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function resetWhere()
    {
        $this->command->resetWhere();

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function revoke($permission)
    {
        $commandClass = $this->getCommandClass('revoke');
        $this->command = new $commandClass($permission);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function select(array $projections, $append = true)
    {
        return $this->command->select($projections, $append);
    }

    /**
     * {@inheritdoc}
     */
    public function type($type)
    {
        return $this->command->type($type);
    }

    /**
     * {@inheritdoc}
     */
    public function to($to)
    {
        return $this->command->to($to);
    }

    /**
     * Truncates an entity.
     *
     * @param  string  $entity
     * @param  boolean $andCluster
     * @return Query
     */
    public function truncate($entity, $andCluster = false)
    {
        try {
            $validator = new RidValidator;
            $validator->check($entity);
            $commandClass = $this->getCommandClass('truncate.record');
        } catch (ValidationException $e) {
            $commandClass = $this->getCommandClass('truncate.class');

            if ($andCluster) {
                $commandClass = $this->getCommandClass('truncate.cluster');
            }
        }

        $this->command = new $commandClass($entity);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $values, $append = true)
    {
        return $this->command->values($values, $append);
    }

    /**
     * {@inheritdoc}
     */
    public function unindex($property, $class = null)
    {
        $commandClass = $this->getCommandClass('index.drop');
        $this->command = new $commandClass($property, $class);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function put(array $values, $class, $append = true)
    {
        $commandClass  = $this->getCommandClass('update.put');
        $this->command = new $commandClass($values, $class, $append);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function canHydrate()
    {
        return $this->getCommand()->canHydrate();
    }

    /**
     * {@inheritdoc}
     */
    public function update($class)
    {
        $commandClass = $this->getCommandClass('update');
        $this->command = new $commandClass($class);

        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $value = null)
    {
        return $this->command->where($condition, $value);
    }

    /**
     * Returns on of the commands that belong to the query.
     *
     * @param  string $id
     * @return mixed
     * @throws \Exception
     */
    protected function getCommandClass($id)
    {
        if (isset($this->commands[$id])) {
            return $this->commands[$id];
        }

        throw new \Exception(sprintf("command %s not found in %s", $id, get_called_class()));
    }

    /**
     * Sets the right class command based on the $action.
     *
     * @param string $action
     * @param string $class
     * @return QueryInterface
     */
    protected function manageClass($action, $class)
    {
        $commandClass = $this->getCommandClass("class." . $action);
        $this->command = new $commandClass($class);

        return $this->command;
    }

    /**
     * Sets the right property command based on the $action.
     *
     * @param string $action
     * @param string $class
     * @param string $property
     * @return QueryInterface
     */
    protected function manageProperty($action, $class, $property, $type = null, $linked = null)
    {
        $commandClass = $this->getCommandClass("property." . $action);
        $this->command = new $commandClass($property, $type, $linked);
        $this->command->on($class);

        return $this->command;
    }

    /**
     * Executes a class or property command checking if the $property parameter
     * is specified.
     * If none,  class command is executed.
     *
     * @param  string $action
     * @param  string $class
     * @param  string $property
     * @param  string $type
     * @param  string $linked
     * @return mixed
     */
    protected function executeClassOrPropertyCommand($action, $class, $property = null, $type = null, $linked = null)
    {
        if ($property) {
            return $this->manageProperty($action, $class, $property, $type, $linked);
        }

        return $this->manageClass($action, $class);
    }

    /**
     * Sets the internal command classes to use
     *
     * @param  array $commands
     * @return true
     */
    protected function setCommands(array $commands)
    {
        $this->commands = array_merge($this->commands, $commands);

        return true;
    }

    /**
     * Returns the raw SQL statement
     */
    public function __toString()
    {
        return $this->getRaw();
    }
}
