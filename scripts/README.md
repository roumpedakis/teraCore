Update Postman collection script

Usage examples:

Add a simple GET endpoint (uses bearer auth header):

```bash
php scripts/update_postman_collection.php --name="Get Users" --method=GET --path="/users" --auth=bearer
```

Add a POST endpoint inside a folder and attach an inline test script:

```bash
php scripts/update_postman_collection.php --name="Create User" --method=POST --path="/users" --group="Users" --tests="pm.test('Status 201', () => pm.response.to.have.status(201));"
```

If you pass `--tests` pointing to a file path, the script will inline the file contents as the Postman test script.

Collection file default: `postman/MyData.postman_collection.json`
Pass `--collection=path/to/collection.json` to use a different file.
