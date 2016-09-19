# mitarbeiterplan

Ugliest-possible prototype for a web-based staff plan (= "Mitarbeiterplan") on a LAMP stack.

Docker-based, so that this stack won't pollute my laptop. See [@tutumcloud/lamp](https://github.com/tutumcloud/lamp):

```bash
docker build -t efg-ludwigshafen/mitarbeiterplan .
docker run -v `pwd`:/app -d -p 80:80 -p 3306:3306 efg-ludwigshafen/mitarbeiterplan
```

[WTFPL](http://www.wtfpl.net/), if you want a license.