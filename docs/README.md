Our documentation can now be built in the Docker.

```
docker build -t docs .
docker run -p 8080:80 docs
```

Open http://localhost:8080/ in your browser.

