Generates wrapper classes based on existing MySQL tables.

Usage: <code>generator.php -d&lt;database&gt; -t&lt;table&gt; [--classname=&lt;classname&gt;] [--has-many=&lt;referent-class&gt;]</code>

It makes a few assumptions. First, names of tables are pluralized. Animals, monsters, and users, not animal, monster, and user. Second, it expects all tables to have at least the following three rows:

* id,  int primary key
* created, datetime
* updated, datetime

Third, the use of __callStatic() necessitates PHP >= 5.3.

Inflector.class.php is a subset of the Ruby [Inflector](http://as.rubyonrails.org/classes/Inflector.html) class.

<hr>

**Example**

Assuming the schema for a database "zoo" is

<pre>
create table `cages` (
    `id`        int auto_increment primary key,
    `created`   datetime,
    `updated`   datetime,
    `name`      varchar(50)
);

create table `animals` (
    `id`        int auto_increment primary key,
    `created`   datetime,
    `updated`   datetime,
    `kingdom`   varchar(128),
    `phylum`    varchar(128),
    `class`     varchar(128),
    `order`     varchar(128),
    `family`    varchar(128),
    `subfamily` varchar(128),
    `genus`     varchar(128),
    `species`   varchar(128),
    `cage_id`   int,
    constraint foreign key (`cage_id`) references `cages`(`id`)
);
</pre>

**Generate Models**

<pre>
./generator.php -d=zoo -t=animals
./generator.php -d=zoo -t=cages --has-many=animals
</pre>

<b>CRUD</b>

<pre>
require_once('Animals.class.php');

# create a new record
$dodo = new Animal();

$dodo->kingdom   = 'Animalia';
$dodo->phylum    = 'Chordata';
$dodo->class     = 'Aves';
$dodo->order     = 'Columbiformes';
$dodo->family    = 'Columbidae';
$dodo->subfamily = 'Raphinae';
$dodo->genus     = 'Raphus';
$dodo->species   = 'R. cucullatus';

$dodo->save();   # record is created in database
$dodo->delete(); # record is removed from database
$dodo->save();   # record is recreated in database (with new id)

# new records can also be created by passing in an array
$brontosaurus = new Animal(array(
    'kingdom'   => 'Animalia',
    'phylum'    => 'Chordata',
    'class'     => 'Reptilia',
    'order'     => 'Saurischia',
    'family'    => 'Diplodocidae',
    'subfamily' => 'Apatosaurinae',
    'genus'     => 'Apatosaurus',
    'species'   => 'A. excelsus'
));
</pre>

**Search**

<pre>
# find the first mammal (default order by created asc)
$mammal = Animal::find_by_class('mammalia');

# find all the vertebrates sorted by family in descending order
$mammals = Animal::find_all_by_phylum('chordata', array('order' => 'family desc'));

# get all the records from animals
$animals = Animal::find();

# use find() to get all carnivorans ordered by genus, reverse alphabetical
$mammals = Animal::find(
    'all',
    array(
      'conditions' => '`order`=?', # wrap keywords as in raw SQL
      'values'=>array('carnivora'),
      'order'=>'genus desc'
    )
);

# get the last animal in the database
$last = Animal::find('first', array('order' => 'created desc'));
</pre>

**Relationships**

<pre>
$cage = new Cage(1); # get cage with id = 1

# get the number of weasels in the cage
echo $cage->animals()->count(array('family'=>'mustilidae'));
</pre>