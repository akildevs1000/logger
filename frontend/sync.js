const axios = require('axios');

const syncEndpoint = 'http://127.0.0.1:8000/api/sync_from_mdb';

let counter = 0;

async function syncData() {
    counter++;
    try {
        const response = await axios.post(syncEndpoint);
        console.log(counter + '. Data synced successfully:', response.data);
    } catch (error) {
        console.error('Error syncing data:', error);
    }
}

setInterval(syncData, 5000);
