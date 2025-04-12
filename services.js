const pm2 = require('pm2');
const path = require('path');

const batchFiles = [
    'all_laravel_services.bat',
    'run_sdk.bat',
];

// Function to execute a batch file using pm2
const runBatchFileWithPM2 = (batchFile) => {
    return new Promise((resolve, reject) => {
        const batPath = path.join(__dirname, batchFile);

        pm2.start({
            name: batchFile,           // Process name
            script: 'cmd.exe',         // Command to execute
            args: ['/c', batPath],     // Arguments to pass (the batch file)
            cwd: __dirname,            // Set current working directory
            windowsHide: true,         // Hide window in Windows (pm2 feature)
        }, (err, apps) => {
            if (err) {
                reject(err); // Reject on error
            } else {
                console.log(`${batchFile} started successfully.`);
                resolve(apps);
            }
        });
    });
};

// Loop through each batch file and execute it
const runBatchFiles = async () => {
    try {
        for (let i = 0; i < batchFiles.length; i++) {
            await runBatchFileWithPM2(batchFiles[i]);
        }
        console.log('All batch files executed successfully.');
    } catch (error) {
        console.error('Error executing batch files:', error);
    } finally {
        // Close pm2 after all tasks are completed
        pm2.disconnect();
    }
};

// Connect to pm2 and run batch files
pm2.connect((err) => {
    if (err) {
        console.error('Failed to connect to pm2:', err);
        process.exit(2);
    } else {
        runBatchFiles();
    }
});
