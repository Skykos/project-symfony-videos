import { Component, OnInit} from '@angular/core';
import {UserService} from '../../services/user.service';
import {Router, ActivatedRoute, Params} from '@angular/router';
import { VideoService} from '../../services/video.service';


@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css'],
  providers: [UserService, VideoService]
})
export class HomeComponent implements OnInit {
  public page_title: string;
  public identity = null;
  public token;
  public status:string;
  public videos;
  public page;
  public next_page;
  public prev_page;
  public number_page;
  
  
  constructor(
    private _userService: UserService,
    private _router: Router,
    private _route: ActivatedRoute,
    private _videoService: VideoService

    
  ) {
    this.page_title = "Mis Videos";
    this.token = this._userService.getToken();
    this.identity = this._userService.getIdentity();
  }

  ngOnInit(): void {
    this._route.params.subscribe(
      params=> {
        var page = +params['page'];
        if(!page){
          page = 1;
          this.prev_page= 1;
          this.next_page = 2;
        }
        this.getVideos(page);
      }
    );
    
  }
  
  

  getVideos(page){
    this._videoService.getVideos(this.token,page).subscribe(
      response=>{
        if(response.status='success'){
          this.videos = response.videos;
          this.status = 'success';
          var number_pages = [];
          for(var i=1; i<= response.total_pages;i++){
            number_pages.push(i);
          }
          this.number_page = number_pages;

          if(page >= 2){
            this.prev_page = page-1;

          }else{
            this.prev_page = 1;
          }

          if(page < response.total_pages ){
            this.next_page = page+1;
          }else{
            this.next_page = response.total_page;
          }
          
        }
      }
      ,error=>{
        this.status='error';
      }
    );
  }

  getThumb(url, size) {
    var video, results, thumburl;
    
     if (url === null) {
         return '';
     }
     
     results = url.match('[\\?&]v=([^&#]*)');
     video   = (results === null) ? url : results[1];
    
     if(size != null) {
         thumburl = 'http://img.youtube.com/vi/' + video + '/'+ size +'.jpg';
     }else{
         thumburl = 'http://img.youtube.com/vi/' + video + '/mqdefault.jpg';
     }
    
      return thumburl;
        
    }

    deleteVideo(id){
      this._videoService.delete(this.token,id).subscribe(
        response=>{
          this.getVideos(this.page);
        },
        error=>{
          console.log(error);
        }
      );
    }

}


