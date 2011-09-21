Generates wrapper classes based on existing MySQL tables.

Usage: generator.php &lt;database&gt; &lt;table&gt; [&lt;classname&gt;]

It makes a few assumptions. First, names of tables are pluralized. Animals, monsters, and users, not animal, monster, and user. Second, it expects all tables to have at least the following three rows:

* id,  int primary key
* created, datetime
* updated, datetime

Third, the use of __callStatic() necessitates PHP >= 5.3.

Inflector.class.php is a subset of the Ruby [Inflector](http://as.rubyonrails.org/classes/Inflector.html) class.

