let updateTimer;
var audio = new Audio();
var audioSrc = "";
var repeat = true;
context = new (window.AudioContext || window.webkitAudioContext)();
analyser = context.createAnalyser();
source = context.createMediaElementSource(audio);

var canvas, ctx, bars,
    x_end, y_end, bar_height,
    freqArray;

bars = 200;

//Audio
audio.addEventListener("ended", function() {
  if(repeat) {
    audio.play();
  } else {
    document.querySelector("#audio-player-toggle").innerHTML = "play";
    audio.currentTime = 0;
  }
});
audio.addEventListener("pause", function() {
  document.querySelector("#audio-player-toggle").innerHTML = "resume";
});
audio.addEventListener("play", function() {
  document.querySelector("#audio-player-toggle").innerHTML = "pause";
});
audio.addEventListener("loadeddata", function() {
  document.querySelector("#audio-player-seek").max = audio.duration;
  document.querySelector("#audio-player-toggle").innerHTML = "pause";

  let min = Math.floor(audio.duration/60);
  let sec = Math.floor(audio.duration % 60);

  if(sec < 10) sec = "0" + sec;
  document.querySelector("#audio-player-track-length").innerHTML = min + ":" + sec;

  audio.play();
});
function audioSet(file) {
  if(audioSrc != file) {
    context.resume();

    audioSrc = file;
    audio.src = audioSrc;
    clearInterval(updateTimer);
    updateTimer = setInterval(audioSeekVisualizerUpdate, 0.01);

    source.connect(analyser);
    analyser.connect(context.destination);
    freqArray = new Uint8Array(analyser.frequencyBinCount);
	
	document.querySelector("#audio-player-song-image").setAttribute("src", "icons/ui/generic-art.png");
	document.querySelector("#audio-player-song-title").innerHTML = file;
	document.querySelector("#audio-player-song-artist").innerHTML = "";
	jsmediatags.read(audio.src, {
	  onSuccess: function(tag) {
		console.log(tag.tags.title + " " + tag.tags.artist);
		document.querySelector("#audio-player-song-title").innerHTML = tag.tags.title;
		document.querySelector("#audio-player-song-artist").innerHTML = tag.tags.artist;
		
		var picture = tag.tags.picture;
		var imageURI = "icons/ui/generic-art.png";
		if(picture != undefined) {
		  var base64String = "";
		  for (var i = 0; i < picture.data.length; i++) {
			base64String += String.fromCharCode(picture.data[i]);
		  }
		  var imageURI = "data:" + picture.format + ";base64," + window.btoa(base64String);
		}
		
		document.querySelector("#audio-player-song-image").setAttribute("src", imageURI);
	  },
	  onError: function(error) {
		console.log(error);
	  }
	});
  }
}
function audioToggle() {
  if(audio.paused) {
    audio.currentTime = document.querySelector("#audio-player-seek").value;
    audio.play();
  }
  else audio.pause();
}
function audioRepeatToggle() {
  var repeatToggle = document.querySelector("#audio-player-repeat-toggle");
  repeat = !repeat;
  if(repeat)
    repeatToggle.classList.add("on");
  else
    repeatToggle.classList.remove("on");
}
function audioSeek() {
  audio.currentTime = document.querySelector("#audio-player-seek").value;
  document.querySelector("#audio-player-seek").blur();
}
function audioSeekVisualizerUpdate() {
  //Seek
  if(document.activeElement != document.querySelector("#audio-player-seek"))
  document.querySelector("#audio-player-seek").value = audio.currentTime;

  let min = Math.floor(document.querySelector("#audio-player-seek").value/60);
  let sec = Math.floor(document.querySelector("#audio-player-seek").value % 60);

  if(sec < 10) sec = "0" + sec;
  document.querySelector("#audio-player-time").innerHTML = min + ":" + sec;

  //Visualizer
  canvas = document.querySelector("#audio-player-visualizer");
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
  ctx = canvas.getContext("2d");

  //update frequency array if audio not paused
  if(!audio.paused)
    analyser.getByteFrequencyData(freqArray);
  
  for(var i = 0; i < bars; i++){
    bar_height = freqArray[i] * 1.5;

    //set color
	  colorNum = i/bars * 360;
	  color = "hsla(" + colorNum + ", 99%, 50%, 0.4)";

    //draw visualizer
    drawCircle(i, bar_height/1.5, freqArray[i], color);
  }
}
function drawBar(num, x1, y1, x2, y2, width, color){
  ctx.strokeStyle = color;
  ctx.lineWidth = width;
  ctx.beginPath();
  ctx.moveTo(x1, y1);
  ctx.lineTo(x2, y2);
  ctx.stroke();
}
const freqArrayRedux = (acc, currVal) => acc + currVal;
function drawCircle(num, bar_height, freq, color) {
  radius = canvas.height/6;
  angle = num/bars * 2 * Math.PI - Math.PI / 2;
  width = 3;
  
  x1 = canvas.width/2 + radius * Math.cos(angle);
  y1 = canvas.height/2 + radius * Math.sin(angle);
  x2 = x1 + bar_height * Math.cos(angle);
  y2 = y1 + bar_height * Math.sin(angle);
  
  drawBar(num, x1, y1, x2, y2, width, color);
}
