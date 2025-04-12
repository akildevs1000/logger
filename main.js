const { app, BrowserWindow } = require('electron');
const path = require('path');
const express = require('express');
const pm2 = require('pm2');

const batchFiles = [
    'all_laravel_services.bat',
    'run_sdk.bat',
];

function createWindow(serverPort) {
    const win = new BrowserWindow({
        width: 800,
        height: 600,
        webPreferences: {
            contextIsolation: true,
        }
    });

    // Load the URL served by Express
    win.loadURL(`http://localhost:${serverPort}`);
}

function startFrontendServer() {
    const expressApp = express();
    const distPath = path.join(__dirname, 'dist');
    expressApp.use(express.static(distPath));

    expressApp.get('*', (req, res) => {
        res.sendFile(path.join(distPath, 'index.html'));
    });

    const serverPort = 3000;
    expressApp.listen(serverPort, () => {
        console.log(`Frontend server running on http://localhost:${serverPort}`);
        createWindow(serverPort); // Load it in the Electron window
    });
}

function runBatchFiles() {
    pm2.connect((err) => {
        if (err) {
            console.error('Failed to connect to PM2:', err);
            return;
        }

        async function runFiles() {
            try {
                for (const file of batchFiles) {
                    await new Promise((resolve, reject) => {
                        const batPath = path.join(__dirname, file);
                        pm2.start({
                            name: file,
                            script: 'cmd.exe',
                            args: ['/c', batPath],
                            cwd: __dirname,
                            windowsHide: true,
                        }, (err) => {
                            if (err) reject(err);
                            else {
                                console.log(`${file} started successfully.`);
                                resolve();
                            }
                        });
                    }).catch(err => {
                        console.error(`❌ Failed to run ${file}:`, err.message);
                    });
                }

                console.log('✅ All batch files executed successfully.');
            } catch (err) {
                console.error('❌ Error executing batch files:', err);
            } finally {
                pm2.disconnect();
            }
        }

        runFiles();
    });
}

app.whenReady().then(() => {
    runBatchFiles();       // run batches
    startFrontendServer(); // serve dist via Express
});


// const AutoLaunch = require('electron-auto-launch');

// const appLauncher = new AutoLaunch({
//     name: 'BatchLauncher',
// });

// appLauncher.enable();