import { Component, OnInit } from '@angular/core';
import {User} from '../../models/user';
import {UserService} from '../../services/user.service';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css'],
  providers: [UserService]
})
export class RegisterComponent implements OnInit {
  public page_title: string;
  public user: User;
  public status; string;
  constructor(
    private _userService: UserService
  ) { 
    this.page_title = "Registrate";
    this.user = new User (1,'','','','','ROLE_USER','');

     
  }

  ngOnInit(): void {
    
  }

  onSubmit(Form){
    this._userService.register(this.user).subscribe(
      response=>{
        if(response.status == 'success'){
          this.status = 'success';
          Form.reset();
        }else{
          this.status = 'error';
        }
      },
      error=>{
        this.status = 'error';
        console.log(error);
        
      }
    );
    
  }

}
