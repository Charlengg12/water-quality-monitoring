<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>pH Gauge</title>
  <style>
    body {
      background: #1e1e1e;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
    }

    .gauge {
      position: relative;
      width: 350px;
      height: 350px;
      border-radius: 50%;
      background: conic-gradient(from 220deg,
          #00c6ff 0deg,
          #28a745 70deg,
          #ffc107 140deg,
          #dc3545 210deg,
          #dc3545 280deg,
          transparent 280deg,
          transparent 360deg);
      border: 10px solid rgb(46, 42, 42);
      box-shadow: inset 0 0 10px #000, 0 0 20px #000;
    }

    .gauge::after {
      content: "";
      position: absolute;
      top: 45px;
      left: 45px;
      right: 45px;
      bottom: 45px;
      background: #1e1e1e;
      border-radius: 50%;
      z-index: 1;
    }

    .gauge::before {
      content: "";
      position: absolute;
      top: 0px;
      left: 0px;
      right: 0px;
      bottom: 0px;
      border-radius: 50%;
      border: 15px solid #1e1e1e;
      z-index: 2;
    }

    .needle {
      position: absolute;
      width: 4px;
      height: 140px;
      background: #ccc;
      bottom: 50%;
      left: 50%;
      transform-origin: bottom center;
      transform: rotate(-90deg);
      z-index: 2;
    }

    .center-dot {
      position: absolute;
      width: 20px;
      height: 20px;
      background: #333;
      border-radius: 50%;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 3;
    }

    .lcd-display {
      position: absolute;
      top: 70%;
      left: 50%;
      transform: translateX(-50%);
      padding: 10px 24px;
      background: #0E0F1A;
      color: #00FFCC;
      font-family: 'Courier New', monospace;
      font-size: 36px;
      font-weight: bold;
      border-radius: 10px;
      border: 2px solid rgb(0, 157, 255);
      box-shadow:
        0 0 10px #00A3FF,
        inset 0 0 5px #004d40,
        0 0 20px rgba(0, 255, 204, 0.3);
      z-index: 4;
      transition: all 0.4s ease-in-out;
    }

    .status-svg {
      position: absolute;
      top: 0;
      left: 0;
      width: 350px;
      height: 350px;
      pointer-events: none;
      z-index: 4;
    }

    .arc-text {
      font-size: 14px;
      font-weight: bold;
      letter-spacing: 1px;
      fill: white;
    }

    .safe-text {
      fill: #00c6ff;
    }

    .neutral-text {
      fill: #28a745;
    }

    .warning-text {
      fill: #ffc107;
    }

    .fail-text {
      fill: #dc3545;
    }

    .gauge-label {
      margin-top: 10px;
      color: white;
      font-size: 32px;
      text-align: center;
      font-weight: bold;
      animation: glow 2s infinite alternate;
    }

    @keyframes glow {
      from {
        text-shadow: 0 0 5px #00f7ff, 0 0 10px #00e0ff;
      }

      to {
        text-shadow: 0 0 15px #00f7ff, 0 0 25px #00e0ff;
      }
    }
  </style>
</head>

<body>

  <div class="gauge">
    <div class="needle" id="needle"></div>
    <div class="center-dot"></div>
    <div class="lcd-display" id="ph-value">7.00</div>

    <svg class="status-svg" viewBox="0 0 350 350">
      <defs>
        <path id="arc-safe" d="M 80 230 A 125 125 0 0 1 145 60" />
        <path id="arc-neutral" d="M 80 120 A 125 125 0 0 1 185 60" />
        <path id="arc-warning" d="M 200 70 A 125 125 0 0 1 285 140" />
        <path id="arc-fail" d="M 280 170 A 100 100 0 0 1 260 240" />
      </defs>

      <text class="arc-text safe-text">
        <textPath href="#arc-safe">SAFE</textPath>
      </text>
      <text class="arc-text neutral-text">
        <textPath href="#arc-neutral">NEUTRAL</textPath>
      </text>
      <text class="arc-text warning-text">
        <textPath href="#arc-warning">WARNING</textPath>
      </text>
      <text class="arc-text fail-text">
        <textPath href="#arc-fail">FAILED</textPath>
      </text>
    </svg>
  </div>

  <div class="gauge-label">pH</div>

</body>

</html>