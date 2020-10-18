@extends('layouts.app')
@section('content')
  <div class="services-content mb-150">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-9 ml-auto mr-auto">
                @if(Session::has('success'))
                    <div class="alert alert-success text-center">
                        {{ Session::get('success') }}
                    </div>
                @endif
                @if(Session::has('error'))
                    <div class="alert alert-danger text-center">
                        {{ Session::get('error') }}
                    </div>
                @endif
                <h2 class="title">CARDCOM SERVICES</h2>
                <ol>
                    <li>
                        <strong>Talkachieve: </strong>
                        Have you ever been called a talkative? According to the oxford dictionary, talkative is a person Fond of or given to talking. Person who is inclined to talk a great deal. That is talking too much in short. Talking much could either be for business or otherwise. Now you can upgrade to a Talkachieve powered by Cardcom Services. You will now talk to achieve great things in family and business just by following a simple yet interesting steps.
                        <br>
                        <strong>How it works:</strong>
                        <br>
                        <i>
                            Using the power of the social media space, you can now convert your followers to airtime. So you can get talking on.
                        </i>
                    </li>
                    <li>
                        <strong>Talk”n”Earn: </strong>
                        Using the Cardcom platform requires completing your transactions by either bank card, Wallet or Voucher. If using your bank card is a challenge, we have created an option for you to use a wallet or a customized Cardcom payment card. Both can be funded directly from your bank.
                        <br>
                        <strong>How it works:</strong>
                        <br>
                        <i>
                            For every amount funded in your wallet or customized Cardcom payment card you earn additional amount at a percentage add to resist. These will give you more amounts in your Wallet or customized Cardcom payment card to make purchases on our Websites.
                        </i>
                    </li>
                    <li>
                        <strong>Talktime: </strong>
                        Are you a goal getter? If you desire to get airtime to talk as you wish here is a deal difficult to ignore for a discerning mind.
                        <br>
                        <strong>How it works:</strong>
                        <br>
                        <i>
                            You will be our most delighted member in the Card community ( Cardcom ). Our community members who wish to take a convenient task will be given talktime (airtime) commensurate to their efforts.
                        </i>
                    </li>
                    <li>
                        <strong>Talkgood: </strong>
                        Every responsible individual will want to be good talked about him or her. Would you want to be talk about in good by parent, family and friends?
                        <br>
                        <strong>How it works:</strong>
                        <br>
                        <i>
                            Cardcom-VTU can deliver airtime to subscriber phone every day at an agreed amount and time. This is part of our talk management strategy for older parent, student, staff, colleagues and so on who by purchasing power may not afford regular recharge or lives in area challenged with recharging. In addition to the amount subscribe mouthwatering bonus is included to further meet up with the agreed duration for the Cardcom-VTU services. For example 15 days subscription can become 20 days of VTU services at agreed amount.
                        </i>
                    </li>
                    <li>
                        <strong>WINPIN: </strong>
                        The loyalties of our registered users are hereby rewarded through this service. Here points are earned as the portal is been used in addition to all our social media channels and participating partner sites.
                        <br>
                        <strong>How it works:</strong>
                        <br>
                        <i>
                            Register at our website address <a href="https://www.cardcom.ng" target="_blank">www.cardcom.ng</a> or <a href="https://www.cardcom.com.ng" target="_blank">www.cardcom.com.ng</a>.Visit any of our websites, social media channels and advertised partner websites to earn points. These points are converted to airtime and deposited in your wallet to make purchases.
                        </i>
                    </li>
                </ol>
                <h2 class="title">OTHER VALUE ADDED SERVICES</h2>
                <p>
                    At <a href="https://www.cardcom.ng">https://www.cardcom.ng</a> the following value added services can be conveniently and instantly be completed.
                </p>
                <ol>
                    <li>
                        <strong>Buy Electricity:</strong> You can recharge your electric metre here by choosing the appropriate electricity distribution companies (Discos) and purchase a token at the comfort of your home and offices. Therefore, avoid the long queues and long delay at the power office. Stay at home and Stay Safe. Purchase / Buy your electricity here.
                    </li>
                    <li>
                        <strong>Cable TV subscriptions:</strong> Don’t miss your favorite movies and program due to expired subscription. Our platform has provided a convenient and affordable means of paying your subscription fees. Stay connected all season; pay DSTV, GOTv, STAR-TIMES AND MORE.
                    </li>
                    <li>
                        <strong>DATA:</strong> It is said data is life and Data is the next oil. Data is driving the world economy. Without Data the modern world will be grounded. Data makes you move and discover the possibilities of near and far. So never run out of Data. This platform will instantly activate your Data subscription plan anytime of the day. Use any of our AIRTIME PACKAGES above to reimburse your Data plan. Also look out for our Data packages at <a href="www.cardcom.com.ng">www.cardcom.com.ng</a> you will be amaze with the opportunity Data can provide.
                    </li>
                </ol>
                <p>
                    We hope our services keep you in touch with us and to the rest of the world.
                </p>
                <p>
                    To subscribe to any of these services, kindly fill the subscription form and submit. Our customer services agents are ever ready to get in touch and sign you up. <a href="https://www.cardcom.com.ng">CLICK HERE</a> TO SUBSCRIBE or fill the form below
                </p>
                <form action="{{ route('subscribe.submit') }}" method="POST">
                    @csrf
                    @method('POST')
                    <div class="row justify-content-center">
                        <div class="col-md-6 col-md-offset-3">
                            <div class="form-group">
                                <input type="text" name="name" id="name" placeholder="Fullname" class="form-control">
                            </div>
                            <div class="form-group">
                                <input type="text" name="email" id="email" placeholder="Email" class="form-control">
                            </div>
                            <div class="form-group">
                                <select name="type" id="type" class="form-control">
                                    <option selected disabled>Select a subscription type</option>
                                    <option>Talkachieve</option>
                                    <option>Talk”n”Earn</option>
                                    <option>Talktime</option>
                                    <option>Talkgood</option>
                                    <option>WINPIN</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-round">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
       </div>
    </div>
  </div>
@endsection