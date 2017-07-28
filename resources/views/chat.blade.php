<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>chat</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style type="text/css">
          html { height: 100%; }
          body { height: 100%; margin: 0; background: #333; text-align: center; }
          .video { height: 200px; margin-top: 5%; background: #000; }
          .localVideo { width: 150px; position: absolute; right: 1.1em; bottom: 1em; border: 1px solid #333; background: #000; }
          #callButton { position: absolute; display: none; left: 50%; font-size: 2em; bottom: 5%; border-radius: 1em; }
        </style>
    </head>
    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>

    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
              <h1 style="color:white;">{{$nick}}</h1>
              <br>
              <video id="localVideo-{{$nick}}"  class="localVideo" autoplay muted></video>

              <button id="callButton" onclick="createOffer()">✆</button>
              @foreach($users as $user)
                @if($user == $nick)
                  @continue;
                @endif
                <video id="remoteVideo-{{$user}}" class='video' autoplay></video>
              @endforeach

            </div>
        </div>
    </body>
    <script>

      var PeerConnection = window.mozRTCPeerConnection || window.webkitRTCPeerConnection;
      var IceCandidate = window.mozRTCIceCandidate || window.RTCIceCandidate;
      var SessionDescription = window.mozRTCSessionDescription || window.RTCSessionDescription;
      navigator.getUserMedia = navigator.getUserMedia || navigator.mozGetUserMedia || navigator.webkitGetUserMedia;

      @foreach($users as $user)
        @if($user == $nick)
          @continue;
        @endif
        var pc_{{$user}};
      @endforeach


      // Step 1. getUserMedia
      navigator.getUserMedia(
          //{ audio: true, video: true },
          { audio: true },
        //{ video: true },
        gotStream,
        function(error) { console.log(error) }
      );

      function gotStream(stream) {
        document.getElementById("callButton").style.display = 'inline-block';
        document.getElementById("localVideo-{{$nick}}").src = URL.createObjectURL(stream);
        console.log(URL.createObjectURL(stream));

          //eval('pc_' + getUser()) = new PeerConnection(null);

          @foreach($users as $user)
            @if($user == $nick)
              @continue;
            @endif
            pc_{{$user}} = new PeerConnection(null);
            pc_{{$user}}.addStream(stream);
            pc_{{$user}}.onicecandidate = gotIceCandidate_{{$user}};
            pc_{{$user}}.onaddstream = gotRemoteStream_{{$user}};
          @endforeach

      }

      function createOffer() {

          //console.log(getUser());

          @foreach($users as $user)
            @if($user == $nick)
              @continue;
            @endif
            pc_{{$user}}.createOffer(
              gotLocalDescription_{{$user}},
              function(error) { console.log(error) },
              { 'mandatory': { 'OfferToReceiveAudio': true, 'OfferToReceiveVideo': true } }
            );
          @endforeach

          //  );
        console.log("WE CALLED!")
      }

      @foreach($users as $user)
        @if($user == $nick)
          @continue;
        @endif
        function createAnswer_{{$user}}(message) {
          console.log("createAnswer_{{$user}}");
          console.log('message.type', message.type );
           pc_{{$user}}.setRemoteDescription(new SessionDescription(message));
           pc_{{$user}}.createAnswer(
             gotLocalDescription_{{$user}},
             function(error) { console.log(error) },
             { 'mandatory': { 'OfferToReceiveAudio': true, 'OfferToReceiveVideo': true } }
           );
           console.log("WE ANSWERED!");
        }

        function gotLocalDescription_{{$user}}(description){
          pc_{{$user}}.setLocalDescription(description);
          sendMessage(description, "{{$user}}");
        }

        function gotIceCandidate_{{$user}}(event){
          if (event.candidate) {
            sendMessage({
              type: 'candidate',
              label: event.candidate.sdpMLineIndex,
              id: event.candidate.sdpMid,
              candidate: event.candidate.candidate
            }, "{{$user}}");
          }
        }



        function gotRemoteStream_{{$user}}(event){
            document.getElementById("remoteVideo-{{$user}}").src = URL.createObjectURL(event.stream);
        }
      @endforeach





        function getUser() {
          if("{{$nick}}" == "user1")
            return "user2";

          return "user1";
        }



      // WebSocket
      var conn = new WebSocket('wss://webrtc.local:443/wss2/');
      conn.open = function(e){
        console.log("Connection established!");;
      }

      conn.onmessage = function(str){
        //obj = Object.create(message[0]);
        //console.log("we got str: " + str.data);


        message = JSON.parse(str.data);


          if(message.dst === "{{$nick}}")
          {
            var src = message.src;
            var variable = 'pc_' + src;

            console.log("I: {{$nick}} src: ",message.src, " type: ",message.type);
            if (message.type === 'offer') {
                console.log('message.type', message.type );
                eval(variable).setRemoteDescription(new SessionDescription(message));
                //createAnswer_user1();
                eval("createAnswer_" + src + '(message)');
                console.log("createAnswer_" + src + '()' );
            }
            else if (message.type === 'answer') {

              eval(variable).setRemoteDescription(new SessionDescription(message));
            }
            else if (message.type === 'candidate') {
                //alert(3);
                var candidate = new IceCandidate({sdpMLineIndex: message.label, candidate: message.candidate});
                eval(variable).addIceCandidate(candidate);
                //alert(4);
            }
          }


      }
      //


      function sendMessage(message, dst){
        var str = JSON.stringify(message);
        str = str.replace("{", '{"src":"{{$nick}}", "dst":"' + dst + '",');
       console.log("We sending: ", str);
          //console.log("we sending: ", str);
          conn.send(str);
      }


    </script>
</html>
