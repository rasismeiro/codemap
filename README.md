Description:
=======
This application tries to map all the classes in a source code directory and shows where they are used in a visual way.

Classes are shown in green and their size is proportional to the number of times they are instantiated or statically used.

With the exception of green, similar colors represent the same class usage proportions.

The graph generated by this code uses the javascript library [d3](https://github.com/mbostock/d3)

Output sample from the WordPress source code:
=======

![WordPress](https://raw.github.com/rasismeiro/codemap/master/print.png)

Generic Example:
=======
http://localhost/index.php?dir=[PathToALocalDirectory]