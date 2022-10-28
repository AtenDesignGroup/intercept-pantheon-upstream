# intercept-client

Client library for interacting with an Intercept API instance.

## Usage

```
import InterceptClient from 'intercept-client';

const testClient = new InterceptClient({
  host: 'http://intercept.test'
});

testClient.coordinator.activate();
```
