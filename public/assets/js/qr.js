/*!
 * LOPANGO QR Code Generator - Pure JavaScript
 * GF(256) with QR primitive polynomial x^8+x^4+x^3+x^2+1 (285)
 */
(function(global){
'use strict';

// ── GF(256) TABLES ──────────────────────────────────────────────────────────
var EXP=new Array(256), LOG=new Array(256);
(function(){
  var x=1;
  for(var i=0;i<255;i++){
    EXP[i]=x; LOG[x]=i;
    x<<=1; if(x&256) x^=285; x&=255;
  }
  EXP[255]=EXP[0];
})();
function gexp(n){while(n<0)n+=255;while(n>=256)n-=255;return EXP[n];}
function glog(n){if(n<1)return 0;return LOG[n];}

// ── GENERATOR POLYNOMIAL ────────────────────────────────────────────────────
function genPoly(n){
  var g=[1];
  for(var i=0;i<n;i++){
    var ng=[g[0]];
    for(var j=1;j<g.length;j++) ng.push(g[j]^gexp(glog(g[j-1])+i));
    ng.push(gexp(glog(g[g.length-1])+i));
    g=ng;
  }
  return g;
}

function getEC(data,ecCount){
  var g=genPoly(ecCount);
  var r=data.slice().concat(new Array(ecCount).fill(0));
  for(var i=0;i<data.length;i++){
    if(!r[i])continue;
    var c=glog(r[i])-glog(g[0]);
    for(var j=0;j<g.length;j++) if(g[j]) r[i+j]^=gexp(glog(g[j])+c);
  }
  return r.slice(data.length);
}

// ── DATA ENCODING ────────────────────────────────────────────────────────────
// RS blocks: [count, totalCodewords, dataCodewords] for ECC-L
var RSB=[null,[1,26,19],[1,44,34],[1,70,55],[2,50,32]];
// Alignment pattern positions
var AP=[null,null,[6,18],[6,22],[6,26]];
// Format info: BCH(ECC-L=01,mask=0)^21522
// Precomputed: format_data = 1<<3|0 = 8, bch(8,1335) = 9174, XOR 21522 = ?
// Actually compute it:
function blen(d){var l=0;while(d){l++;d>>>=1;}return l;}
function bch15(d){
  var r=d<<10;
  while(blen(r)-blen(1335)>=0) r^=1335<<(blen(r)-blen(1335));
  return(d<<10)|r^21522;
}

function encodeData(text, dataCount){
  var bytes=[];
  for(var i=0;i<text.length;i++) bytes.push(text.charCodeAt(i)&255);
  var bits=[0,1,0,0]; // byte mode
  var L=bytes.length;
  for(var i=7;i>=0;i--) bits.push((L>>i)&1);
  for(var i=0;i<bytes.length;i++) for(var j=7;j>=0;j--) bits.push((bytes[i]>>j)&1);
  for(var i=0;i<4&&bits.length<dataCount*8;i++) bits.push(0);
  while(bits.length%8) bits.push(0);
  while(bits.length<dataCount*8){
    for(var j=7;j>=0;j--) bits.push((0xEC>>j)&1);
    if(bits.length<dataCount*8) for(var j=7;j>=0;j--) bits.push((0x11>>j)&1);
  }
  var r=[];
  for(var i=0;i<bits.length;i+=8){var b=0;for(var j=0;j<8;j++)b=(b<<1)|(bits[i+j]||0);r.push(b);}
  return r;
}

function getVersion(text){
  var n=0;
  for(var i=0;i<text.length;i++){var c=text.charCodeAt(i);n+=(c<128?1:c<2048?2:3);}
  // v1-L: 17 bytes, v2-L: 32 bytes, v3-L: 53 bytes
  if(n<=17)return 1;if(n<=32)return 2;if(n<=53)return 3;return 4;
}

// ── QR MATRIX GENERATION ─────────────────────────────────────────────────────
function makeMatrix(text,ver){
  ver=ver||2;
  var N=ver*4+17;
  var mod=[];var fix=[];
  for(var i=0;i<N;i++){mod[i]=[];fix[i]=[];for(var j=0;j<N;j++){mod[i][j]=false;fix[i][j]=false;}}

  // Finder pattern
  function finder(r,c){
    for(var dr=-1;dr<=7;dr++)for(var dc=-1;dc<=7;dc++){
      var nr=r+dr,nc=c+dc;
      if(nr<0||nr>=N||nc<0||nc>=N)continue;
      fix[nr][nc]=true;
      mod[nr][nc]=(0<=dr&&dr<=6&&(dc===0||dc===6))||(0<=dc&&dc<=6&&(dr===0||dr===6))||(2<=dr&&dr<=4&&2<=dc&&dc<=4);
    }
  }
  finder(0,0);finder(0,N-7);finder(N-7,0);

  // Timing
  for(var i=8;i<N-8;i++){
    if(!fix[6][i]){fix[6][i]=true;mod[6][i]=(i%2===0);}
    if(!fix[i][6]){fix[i][6]=true;mod[i][6]=(i%2===0);}
  }
  // Dark module
  mod[N-8][8]=true;fix[N-8][8]=true;

  // Alignment (ver>=2)
  if(ver>=2&&AP[ver]){
    var pos=AP[ver];
    for(var pi=0;pi<pos.length;pi++)for(var pj=0;pj<pos.length;pj++){
      var ar=pos[pi],ac=pos[pj];
      if(fix[ar][ac])continue;
      for(var dr=-2;dr<=2;dr++)for(var dc=-2;dc<=2;dc++){
        fix[ar+dr][ac+dc]=true;
        mod[ar+dr][ac+dc]=(dr===-2||dr===2||dc===-2||dc===2||(dr===0&&dc===0));
      }
    }
  }

  // Format info (ECC-L=1 in qrcode convention, mask=0)
  var fmt=bch15(1<<3|0);
  var fp=[[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];
  var gp=[[N-1,8],[N-2,8],[N-3,8],[N-4,8],[N-5,8],[N-6,8],[N-7,8],[8,N-8],[8,N-7],[8,N-6],[8,N-5],[8,N-4],[8,N-3],[8,N-2],[8,N-1]];
  for(var i=0;i<15;i++){
    var v=((fmt>>i)&1)===1;
    fix[fp[i][0]][fp[i][1]]=true;mod[fp[i][0]][fp[i][1]]=v;
    fix[gp[i][0]][gp[i][1]]=true;mod[gp[i][0]][gp[i][1]]=v;
  }

  // Encode + RS
  var rs=RSB[ver];
  var data=encodeData(text,rs[2]);
  var ec=getEC(data,rs[1]-rs[2]);
  var all=data.concat(ec);

  // To bits
  var bits=[];
  for(var i=0;i<all.length;i++) for(var j=7;j>=0;j--) bits.push((all[i]>>j)&1);

  // Place data with mask 0: (row+col)%2==0 → invert
  var bi=0,up=true,col=N-1;
  while(col>0){
    if(col===6)col--;
    for(var rs2=0;rs2<N;rs2++){
      var row=up?N-1-rs2:rs2;
      for(var dc=0;dc<2;dc++){
        var c=col-dc;
        if(fix[row][c])continue;
        var bit=(bits[bi++]||0);
        if((row+c)%2===0)bit^=1;
        mod[row][c]=(bit===1);
      }
    }
    up=!up;col-=2;
  }
  return{modules:mod,size:N};
}

// ── DRAW QR CODE ──────────────────────────────────────────────────────────────
function draw(canvasId,data,size){
  size=size||120;
  var canvas=document.getElementById(canvasId);
  if(!canvas)return;
  // Remove previous
  var ex=document.getElementById(canvasId+'_qr');
  if(ex)ex.remove();

  if(!data||data.indexOf('XXXX')>=0||/^KIN-[A-Z]+-[A-Z]+-0{3}-U\d+$/.test(data)){
    canvas.style.display='block';canvas.width=size;canvas.height=size;
    var ctx=canvas.getContext('2d');
    ctx.fillStyle='#f2f6f2';ctx.fillRect(0,0,size,size);
    ctx.strokeStyle='#c8d8c8';ctx.strokeRect(2,2,size-4,size-4);
    ctx.fillStyle='#6a8a6a';ctx.font='bold '+Math.floor(size/8)+'px sans-serif';
    ctx.textAlign='center';ctx.fillText('QR',size/2,size/2+5);
    return;
  }

  try{
    var ver=getVersion(data);
    var qr=makeMatrix(data,ver);
    var mc=qr.size;
    var cs=Math.max(2,Math.floor((size-8)/mc));
    var mg=Math.floor((size-mc*cs)/2);
    var ts=mc*cs+mg*2;

    var tc=document.createElement('canvas');tc.width=ts;tc.height=ts;
    var ctx=tc.getContext('2d');
    ctx.fillStyle='#ffffff';ctx.fillRect(0,0,ts,ts);
    ctx.fillStyle='#0f4c35';
    for(var r=0;r<mc;r++)for(var c=0;c<mc;c++)
      if(qr.modules[r][c])ctx.fillRect(mg+c*cs,mg+r*cs,cs,cs);

    var img=document.createElement('img');
    img.id=canvasId+'_qr';
    img.width=size;img.height=size;
    img.src=tc.toDataURL('image/png');
    img.style.cssText='display:block;border-radius:2px;';
    canvas.style.display='none';
    canvas.parentNode.insertBefore(img,canvas.nextSibling);
  }catch(e){
    console.error('QR error:',e);
    canvas.style.display='block';canvas.width=size;canvas.height=size;
    var ctx=canvas.getContext('2d');
    ctx.fillStyle='#fff';ctx.fillRect(0,0,size,size);
    ctx.strokeStyle='#0f4c35';ctx.lineWidth=2;ctx.strokeRect(2,2,size-4,size-4);
    ctx.fillStyle='#0f4c35';ctx.font=Math.floor(size/15)+'px sans-serif';
    ctx.textAlign='center';ctx.fillText(data.substring(0,15),size/2,size/2);
  }
}

function toDataURL(data,size){
  try{
    var ver=getVersion(data||'X');
    var qr=makeMatrix(data,ver);
    var mc=qr.size;size=size||200;
    var cs=Math.max(4,Math.floor((size-16)/mc));
    var mg=Math.floor((size-mc*cs)/2);
    var tc=document.createElement('canvas');tc.width=size;tc.height=size;
    var ctx=tc.getContext('2d');
    ctx.fillStyle='#fff';ctx.fillRect(0,0,size,size);
    ctx.fillStyle='#0f4c35';
    for(var r=0;r<mc;r++)for(var c=0;c<mc;c++)
      if(qr.modules[r][c])ctx.fillRect(mg+c*cs,mg+r*cs,cs,cs);
    return tc.toDataURL('image/png');
  }catch(e){return'';}
}

document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('canvas[data-qr]').forEach(function(canvas){
    var d=canvas.dataset.qr,s=parseInt(canvas.dataset.qrSize||'96');
    if(d)draw(canvas.id,d,s);
  });
});

global.LopangoQR={draw:draw,toDataURL:toDataURL};
})(window);
