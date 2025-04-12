const dgram = require('dgram');

// Create a UDP server
const server = dgram.createSocket('udp4');

// Define the server behavior on receiving messages
server.on('message', (msg, rinfo) => {
  // Debugging to check the raw data
  console.log("🚀 ~ server.on ~ rinfo:", rinfo);
  console.log("🚀 ~ server.on ~ msg:", msg);
  
  // Try to convert the buffer to a UTF-8 string (ignore any non-UTF-8 chars)
  let message = msg.toString();
  
  // Log the decoded message
  console.log(`Received message: "${message}" from ${rinfo.address}:${rinfo.port}`);
  
  // Send a response back to the client
  const response = Buffer.from(`Server received: ${message}`);
  server.send(response, rinfo.port, rinfo.address, (err) => {
    if (err) {
      console.log('Error sending response:', err);
    } else {
      console.log('Response sent');
    }
  });
});

// Bind the server to IP 192.168.2.5 and port 7001
server.bind(7001, '192.168.2.5', () => {
  console.log('UDP server listening on 192.168.2.5:7001');
});
