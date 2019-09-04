var filePath = './src/schema/shema.json';

var dotenv = require('dotenv').config();
var OrbitSchemaFromOpenApi = require('orbit-schema-from-openapi');

var interceptSchema = new OrbitSchemaFromOpenApi({
  base: process.env.DOMAIN,
  oauth: {
    grant_type: process.env.GRANT_TYPE,
    client_id: process.env.CLIENT_ID,
    client_secret: process.env.CLIENT_SECRET,
    username: process.env.USERNAME,
    password: process.env.PASSWORD
  }
});

interceptSchema.generate()
  .then(interceptSchema.writeToFile(filePath))
  .catch((err) => console.log(err));
