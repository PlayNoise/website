<?php
session_start();

// Generate a unique session ID if not already set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = uniqid('user_', true);
}

$uploadDir = 'uploads/';

// Function to delete files older than 1 hour
function deleteOldFiles($directory, $maxAgeInSeconds) {
    if (is_dir($directory)) {
        foreach (scandir($directory) as $file) {
            $filePath = $directory . $file;
            if (is_file($filePath)) {
                $fileAge = time() - filemtime($filePath);
                if ($fileAge > $maxAgeInSeconds) {
                    unlink($filePath); // Delete the file
                }
            }
        }
    }
}

// Delete old files from the uploads directory
deleteOldFiles($uploadDir, 3600); // 1 hour = 3600 seconds

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $fileName = $userId . '_' . uniqid('audio_', true) . '.wav';
    $filePath = $uploadDir . $fileName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'File uploaded successfully.',
                'fileName' => $fileName,
                'filePath' => $filePath,
                'userId' => $userId
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or an error occurred.']);
    }
    exit;
}

// Fetch the most recent file for the user
function getMostRecentFile($uploadDir, $userId) {
    $files = [];
    if (is_dir($uploadDir)) {
        foreach (scandir($uploadDir) as $file) {
            if (strpos($file, $userId) === 0) {
                $files[] = $file;
            }
        }
    }

    if (empty($files)) {
        return null;
    }

    usort($files, function ($a, $b) use ($uploadDir) {
        return filemtime($uploadDir . $b) - filemtime($uploadDir . $a);
    });

    return $files[0];
}

$mostRecentFile = getMostRecentFile($uploadDir, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlayNoise From Speech to Musical Instrument</title>
    <link rel="icon" type="image/png" href="https://avatars.githubusercontent.com/u/183313663?s=16&v=4">

    <link href="./css/bootstrap.min.css" rel="stylesheet" />
    <link  href="./css/prism-tomorrow.min.css" rel="stylesheet"/>
    <script src="../pn-library.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
      <div class="container">
        <a class="navbar-brand" href="#">
          <img
          src="https://avatars.githubusercontent.com/u/183313663?s=50&v=4"
          alt="PlayNoise.js Logo"
          style="height: 40px; margin-right: 10px"
          />
          PlayNoise.js
      </a>
      <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#navbarNav"
      aria-controls="navbarNav"
      aria-expanded="false"
      aria-label="Toggle navigation"
      >
      <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="#features">Features</a>
      </li>
      <li class="nav-item">
          <a class="nav-link" href="#get-started">Get Started</a>
      </li>
      <li class="nav-item">
          <a class="nav-link" href="#supported-instruments">Supported Instruments</a>
      </li>
      <li class="nav-item">
          <a class="nav-link" href="#contact">Contact</a>
      </li>
      <li class="nav-item">
          <a
          class="nav-link"
          href="https://github.com/PlayNoise/PlayNoise.js"
          target="_blank"
          >GitHub</a
          >
      </li>
  </ul>
</div>
</div>
</nav>

<!-- Hero Section -->
<header class="bg-primary text-white text-center py-5">
  <div class="container">
    <h1>Welcome to PlayNoise.js</h1>
    <p class="lead">
      Create music directly in your browser with voice-to-instrument
      conversion and YAML-based scores!
  </p>
  <button class="btn btn-light btn-lg" id="toggleButton">Try It</button>
  <div class="row mt-4">
      <div class="col-md-6 text-center">
        <p><strong>Original Audio:</strong></p>
        <audio controls>
          <source src="recording2.wav" type="audio/wav" />
          Your browser does not support the audio element.
      </audio>
  </div>
  <div class="col-md-6 text-center">
    <p><strong>Processed Audio Banjo Instrument:</strong></p>
    <audio controls>
      <source src="play-noise-Banjo.wav" type="audio/wav" />
      Your browser does not support the audio element.
  </audio>
</div>
</div>

</div>
</header>


<!-- User Files Section -->
<section id="user-files" class="py-5">
    <div class="container">
<!-- Instrument Selection Dropdown -->
<div class="container text-center mt-3">
    <label for="instrumentSelect">Choose an Instrument:</label>
    <select id="instrumentSelect" class="form-select w-50 mx-auto">
        <option value="trumpet">Trumpet</option>
        <option value="funcklead">FunckLead</option>
        <option value="cello">cello</option>        
        <option value="thickbass">Guitar</option>
        <option value="banjo">Banjo</option>
    </select>
</div>
        <h2 class="text-center">Your Original Audio</h2>
        <?php if ($mostRecentFile): ?>
            <div class="text-center">
                <p id ="alert" style="display:none">Your proccessed file will be downloaded in 8 seconds</p>
                <p><strong>File Name:</strong> <?= htmlspecialchars($mostRecentFile) ?></p>
                <a href="<?= $uploadDir . $mostRecentFile ?>" download class="btn btn-primary">Download Audio</a>
                <button class="btn btn-success" onclick="runPNExample()">Download Instrumental</button>                
            </div>
        <?php else: ?>
            <p class="text-center">You have not uploaded any files yet.</p>
        <?php endif; ?>
    </div>

<!-- Embed iframe section -->
  <div class="container">
    <br/><br/>
    <h2 class="text-center">Try YAML to Audio Conversion  </h2>
    <div class="text-center">
      <iframe src="iframe.html" width="100%" height="460px" style="border: none;"></iframe>
    </div>
  </div>

</section>
<!-- Features Section -->
<section id="features" class="py-5">
  <div class="container">
    <h2 class="text-center">Features</h2>
    <div class="row mt-4">
      <div class="col-md-4 text-center">
        <h4>Voice-to-Instrument Conversion</h4>
        <p>
          Convert recorded voices into musical tones for unique sound
          generation.
      </p>
  </div>
  <div class="col-md-4 text-center">
    <h4>YAML-Based Scores</h4>
    <p>Effortlessly write music using simple YAML syntax.</p>
</div>
<div class="col-md-4 text-center">
    <h4>WAV File Export</h4>
    <p>
      Generate stereo audio and export it as downloadable WAV files.
  </p>
</div>
</div>
</div>
</section>

<!-- Get Started Section -->
<section id="get-started" class="bg-light py-5">
  <div class="container">
    <h2 class="text-center">Get Started</h2>
    <p class="text-center">
      Use the following code snippets to quickly get started with
      PlayNoise.js:
  </p>

  <h5>Voice To Instrument:</h5>
  <pre
  class="bg-dark text-white p-3 rounded"
  ><code class="language-javascript">&lt;script src="pn-library.js"&gt;&lt;/script&gt;
    &lt;script&gt;
    async function runPNExample() {
        console.log(PN);  // Debug the PN object
        PN.instrument('thickbass'); // Choose an instrument
        const song = await PN.singVoice('recording2.wav'); // Convert voice to music
        console.log("Song created:", song);
        setTimeout(() => {
            PN.save(); // Save the audio as WAV after a delay
        }, 8000);
    }
    runPNExample();
&lt;/script&gt;</code></pre>

<h5>YAML Song Data:</h5>
<pre
class="bg-dark text-white p-3 rounded"
><code class="language-javascript">// Define song data
&lt;script src="pn-library.js"&gt;&lt;/script&gt;
    &lt;script&gt;
length: 0.4
instrument: banjo
harmonic: first
volume: 5000
sections:
  [
    {
      C1: [a4, c5, e5, 2:f5, 3:b4, a5, b5, g5, a5],
      C2: [4:a3, b3, d4, f4, b4, 2:c5, 2:e4],
    },
  ]
      `;

      const songData = jsyaml.load(yamlContent)
      // Create and play the song
      PN.instrument("banjo");
      PN.setVolume(0.5); // Set volume level
      const song = PN.createSong(songData); // Create a song from the input data
      PN.save(song);
</code></pre>


</div>
</section>
<!-- Supported Instruments Section -->
<section id="supported-instruments" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center">Supported Instruments</h2>
        <p class="text-center">Explore the variety of instruments you can use with PlayNoise.js:</p>
        <div class="row mt-4">
            <div class="col-md-3 text-center">
                <h4>ThickBass</h4>
            </div>
            <div class="col-md-3 text-center">
                <h4>Banjo</h4>
            </div>
            <div class="col-md-3 text-center">
                <h4>FunckLead</h4>
            </div>
            <div class="col-md-3 text-center">
                <h4>60's Organ</h4>
            </div>
            <div class="col-md-3 text-center">
                <h4>Cello</h4>
            </div>
            <div class="col-md-3 text-center">
                <h4>Guitar</h4>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5">
  <div class="container">
    <h2 class="text-center">Contact Us</h2>
    <p class="text-center">Located in South West, Cameroon</p>
    <p class="text-center">
      Email: <a href="mailto:office@playnoise.org">office@playnoise.org</a>
  </p>
</div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-3">
  <div class="container">
    <p>&copy; 2025 PlayNoise.js. All rights reserved.</p>
    <ul class="list-inline">
      <li class="list-inline-item">
        <a href="https://github.com/PlayNoise/PlayNoise.js/tree/main/examples" class="text-white">Examples</a>
      </li>
      <li class="list-inline-item">
        <a href="https://github.com/PlayNoise/PlayNoise.js/tree/main?tab=readme-ov-file#installation" class="text-white">Installation</a>
      </li>
      <li class="list-inline-item">
        <a href="https://github.com/PlayNoise/PlayNoise.js/tree/main/docs" class="text-white">Documentation</a>
      </li>
      <li class="list-inline-item">
        <a href="https://github.com/PlayNoise/PlayNoise.js/fork" class="text-white" target="_blank">Fork on GitHub</a>
      </li>
    </ul>
  </div>
</footer>



<script>
    let audioContext;
    let mediaRecorder;
    let audioChunks = [];
    let isRecording = false;

    function writeWaveHeader(dataLength, sampleRate) {
        const numChannels = 1, bitsPerSample = 16;
        const byteRate = sampleRate * numChannels * bitsPerSample / 8;
        const blockAlign = numChannels * bitsPerSample / 8;

        const buffer = new Uint8Array(44);
        const view = new DataView(buffer.buffer);

            buffer.set([82, 73, 70, 70], 0); // "RIFF"
            view.setUint32(4, 36 + dataLength, true); // Chunk size
            buffer.set([87, 65, 86, 69], 8); // "WAVE"

            buffer.set([102, 109, 116, 32], 12); // "fmt "
            view.setUint32(16, 16, true); // Subchunk1Size (16 for PCM)
            view.setUint16(20, 1, true);  // AudioFormat (1 for PCM)
            view.setUint16(22, numChannels, true); // NumChannels
            view.setUint32(24, sampleRate, true); // SampleRate
            view.setUint32(28, byteRate, true); // ByteRate
            view.setUint16(32, blockAlign, true); // BlockAlign
            view.setUint16(34, bitsPerSample, true); // BitsPerSample

            buffer.set([100, 97, 116, 97], 36); // "data"
            view.setUint32(40, dataLength, true); // Subchunk2Size (data length)

            return buffer;
        }

        function uploadWavFile(audioBuffer, sampleRate) {
            const bitsPerSample = 16;
            const maxVolume = 32767;

            const buffer = new Uint8Array(audioBuffer.length * 2);
            const view = new DataView(buffer.buffer);

            for (let i = 0; i < audioBuffer.length; i++) {
                const sample = Math.max(-1, Math.min(1, audioBuffer[i]));
                const intSample = Math.floor(sample * maxVolume);
                view.setInt16(i * 2, intSample, true);
            }

            const waveHeader = writeWaveHeader(buffer.length, sampleRate);

            const wavFile = new Uint8Array(waveHeader.length + buffer.length);
            wavFile.set(waveHeader, 0);
            wavFile.set(buffer, waveHeader.length);

            const blob = new Blob([wavFile], { type: 'audio/wav' });

            const formData = new FormData();
            formData.append('file', blob, 'recording.wav');

            fetch('', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                console.log('Upload successful:', data);
                location.reload(); // Reload to update file list
            })
            .catch(error => {
                console.error('Error uploading file:', error);
            });
        }

        function toggleRecording() {
            const button = document.getElementById('toggleButton');

            if (!isRecording) {
                navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const input = audioContext.createMediaStreamSource(stream);

                    const recorder = audioContext.createScriptProcessor(4096, 1, 1);
                    input.connect(recorder);
                    recorder.connect(audioContext.destination);

                    recorder.onaudioprocess = e => {
                        const audioData = e.inputBuffer.getChannelData(0);
                        audioChunks.push(...audioData);
                    };

                    mediaRecorder = {
                        stop: () => {
                            recorder.disconnect();
                            input.disconnect();
                            stream.getTracks().forEach(track => track.stop());

                            uploadWavFile(audioChunks, audioContext.sampleRate);
                            audioChunks = [];
                        }
                    };

                    button.textContent = "Stop Recording";
                    isRecording = true;
                });
            } else {
                if (mediaRecorder) {
                    mediaRecorder.stop();
                }
                button.textContent = "Start Recording";
                isRecording = false;
            }
        }

        document.getElementById('toggleButton').addEventListener('click', toggleRecording);
    </script>
    <script type="text/javascript">
      const progressBar = PN.createProgressBar({
        max: 100,
        value: 0,
        id: "encoderProgressBar",
    });
    
      async function runPNExample() {
        document.getElementById("alert").style.display = "block";
        const selectedInstrument = document.getElementById('instrumentSelect').value || 'banjo';

        try {
        console.log(PN);  // Debug the PN object
        PN.instrument(selectedInstrument); // Use the selected instrument

        // Ensure the PHP variable is properly passed as a string
        const voiceFilePath = "<?= htmlspecialchars($uploadDir . $mostRecentFile, ENT_QUOTES, 'UTF-8') ?>";

        console.log("Voice file path:", voiceFilePath);  // Debug the file path
        const song = await PN.singVoice(voiceFilePath); // Convert voice to music

        console.log("Song created:", song);

        setTimeout(() => {
            PN.save(); // Save the audio as WAV after a delay
        }, 8000);
    } catch (error) {
        console.error("Error in runPNExample:", error);
    }
}



</script>
    <script>
document.querySelector('iframe').addEventListener('load', function() {
    window.scrollTo(0, 0); // Scroll to the top when iframe is loaded
});
        
    </script>

<script src="./js/bootstrap.bundle.min.js"></script>
<script src="./js/prism.min.js"></script>

</body>
</html>
