<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>GN Math Portal</title>
<style>
body{margin:0;font-family:Arial;background:#1e1e2f;color:#fff;}
#loadingScreen{position:fixed;top:0;left:0;width:100%;height:100%;background:#111;display:flex;justify-content:center;align-items:center;flex-direction:column;z-index:99999;}
#progressBar{width:80%;height:20px;background:#333;margin-top:10px;border-radius:10px;overflow:hidden;}
#progressBar div{height:100%;width:0%;background:#fc2651;}
#mainContainer{display:none;padding:10px;}
img,audio{max-width:100%;display:block;margin:10px auto;}
</style>
</head>
<body>

<div id="loadingScreen">
<h1>Loading...</h1>
<div id="progressBar"><div></div></div>
</div>

<div id="mainContainer">
<div id="githubContent"></div>
<div id="imageContainer"></div>
<div id="audioContainer"></div>
</div>

<script>
// CONFIG: Replace these with your GitHub raw URLs
const githubHTMLUrl = 'https://raw.githubusercontent.com/William121444444/gn-math/main/main.html';
const imageURL = 'https://raw.githubusercontent.com/William121444444/gn-math/main/image.png';
const audioURL = 'https://raw.githubusercontent.com/William121444444/gn-math/main/sound.mp3';
const ttsText = "Welcome to GN Math Portal!";

// Fetch HTML from GitHub
fetch(githubHTMLUrl)
  .then(resp => resp.text())
  .then(html => { document.getElementById('githubContent').innerHTML = html; })
  .catch(() => { document.getElementById('githubContent').innerHTML = "<p style='color:red'>Failed to load content</p>"; });

// Components to load
let components = [
    {type:'image',src:imageURL,container:'imageContainer'},
    {type:'audio',src:audioURL,container:'audioContainer'}
];

let loaded=0,total=components.length;
function updateProgress(){
    loaded++;
    let percent = Math.floor((loaded/total)*100);
    document.getElementById('progressBar').firstElementChild.style.width = percent + '%';
    if(loaded >= total){
        document.getElementById('loadingScreen').style.display = 'none';
        document.getElementById('mainContainer').style.display = 'block';
        if(ttsText !== '') speechSynthesis.speak(new SpeechSynthesisUtterance(ttsText));
    }
}

// Load images and audio
components.forEach(c=>{
    let didLoad=false;
    if(c.type==='image' && c.src){
        let img=document.createElement('img'); img.src=c.src;
        img.onload=()=>{if(!didLoad){didLoad=true;updateProgress();}};
        img.onerror=()=>{if(!didLoad){didLoad=true;updateProgress();}};
        document.getElementById(c.container).appendChild(img);
    } else if(c.type==='audio' && c.src){
        let audio=document.createElement('audio'); audio.src=c.src; audio.autoplay=true;
        audio.onloadeddata=()=>{if(!didLoad){didLoad=true;updateProgress();}};
        audio.onerror=()=>{if(!didLoad){didLoad=true;updateProgress();}};
        document.getElementById(c.container).appendChild(audio);
    } else updateProgress();
    setTimeout(()=>{if(!didLoad){didLoad=true;updateProgress();}},5000);
});
</script>

</body>
</html>
