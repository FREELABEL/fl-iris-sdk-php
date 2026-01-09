# Dima Meeting Transcript Summary - January 9, 2026

**Meeting Date:** January 9, 2026  
**Attendees:** Alex Mayo, Dima Semyansky  
**Type:** POC Discussion & Project Scoping  
**Duration:** ~30 minutes

---

## Opening Context

Dima opened by explaining his overall vision for using AI agents instead of building traditional features in code.

---

## Part 1: Internal Admin Automation Concept

### Dima's Vision

**Quote:**
> "Right in the app for an admin to do. But then if we want to say if someone hasn't logged in in so many days, I want to deactivate them, I want to build a feature for that anymore. I want to build an agent that'll do that. And I want to schedule it or trigger it by something or whatever, right?"

**Key Points:**
- Wants to replace admin features with agents
- Example use case: Auto-deactivate users who haven't logged in X days
- Wants scheduling or event-triggered execution
- No code building preferred

### Data Retrieval & Metrics

**Quote:**
> "And so any time of admin actions or even data retrieval, if I want to calculate a certain metric, and again, I can build a report for it as well quickly, but in a pinch, I can use an agent and say, hey, how many users joined yesterday? I don't want to build this app, I don't want to build anything in code. I just want to do an agent that'll obviously call the right tools and give me that information."

**Key Points:**
- Agents for calculating metrics on-demand
- Alternative to building reports
- Quick answers without writing code
- Agent calls appropriate tools to get data

### Summary Statement

**Quote:**
> "So that's sort of the overall concept of what I'm interested in."

---

## Part 2: Current Projects Overview

### Project 1: Internal System (Already Built)

**Quote:**
> "And I have a couple specific projects. One I already built internal agentic very simple, limited thing and it works. I've had to build everything from scratch every time, and I'd rather consider using a platform that has a lot of capabilities already."

**Key Points:**
- Already built a simple agentic system
- Works but limited
- Built everything from scratch
- Looking for platform with built-in capabilities
- Wants to avoid rebuilding each time

---

## Part 3: Fire Department Scheduling Project (MAJOR)

### Project Introduction

**Quote:**
> "The second project that we have is a lot more sophisticated. There's an enterprise project and that's a project for fire departments and emergency services and a specific application is called scheduling."

### The Scheduling Challenge

**Quote:**
> "So if you imagine a fire department, they have a 24 hour shift, sometimes a 48 hour shift, it depends. And that's where the nuance is. And you need to schedule people. And you know what happens to the schedule to put someone on and they can't show up. And there's overtime. There's a lot going on."

**Key Points:**
- 24-48 hour shifts (varies)
- People can't show up
- Overtime calculations
- Complex scheduling requirements

### Multi-Tenant Complexity

**Quote:**
> "And each fire department, which shows tens of thousands of them around the country, has its own little rules and settings, and new ones."

**Critical Scale Information:**
- **Tens of thousands** of fire departments nationwide
- Each has unique rules and settings
- Rules evolve and change over time
- Multi-tenant architecture required

### The Agent Use Case

**Quote:**
> "And so what we need is to implement a very flexible functionality where an agent, so the user says, hey, I need to replace this person, the person is late. We would have an agent that would be intelligently selecting the candidates, interacting with those candidates, getting confirmation, and interacting with our supervisors, based on the hierarchy of the specific department, and then finally making that decision, replaced not replaced, right?"

**Agent Workflow:**
1. User: "Need to replace this person, they're late"
2. Agent: Intelligently selects candidate replacements
3. Agent: Interacts with candidates
4. Agent: Gets confirmations
5. Agent: Interacts with supervisors (hierarchy-based)
6. Agent: Makes final decision (replaced/not replaced)

### Scale of Use Cases

**Quote:**
> "And it's just one example. There's hundreds at least, maybe thousands of use cases. And I expect there are going to be a lot more than I can even imagine that they can even imagine today once they get these tools in their hands."

**Key Points:**
- Replacement is ONE example
- Hundreds to thousands of use cases exist
- More will emerge once tools are available
- Unpredictable future expansion

### Summary Statement

**Quote:**
> "So that's the overall thing that I want to try to attack and consider your platform as foundation for it or engine for it."

---

## Part 4: Alex's Response - Staff Management Tool

### Alex's Initial Assessment

**Quote (Alex):**
> "Okay, that makes sense. We have a staff management tool that we already built. I think that we could probably extend that and build off of that and extend it and make a little bit more flexible and maybe make some of the capabilities more atomic so it can do a lot more and be flexible. Because it does have the capability."

**Key Points:**
- Staff management tool already exists
- Can be extended
- Needs more atomic capabilities
- Needs more flexibility
- Foundation is there

---

## Part 5: BA Conference Strategy (Alex's Idea)

### The BA Opportunity

**Quote (Alex):**
> "I don't think I was going to tell you, Alex, I think I can bring this to BA because a lot of people want something in BA, but they don't want to spend money right now on a larger project, which then if we bring this to BA, which sort of the backend is already there, all we need to do is build the agents, that could be a much more appealing proposition for them."

**Key Points:**
- BA network wants solutions
- Don't want to spend big money upfront ($20-30k)
- Backend already built
- Just need to build agents
- More appealing value proposition

### Upsell to Core Product

**Quote (Alex):**
> "And I could probably bring some of them in as well. And they would be more interested in your core product where you have the CRM and you have these prebuilt apps that they could take advantage of right away."

**Strategy:**
- Bring BA members in through agents
- Upsell to full IRIS platform
- CRM access
- Pre-built apps available

---

## Part 6: BA Meeting Context (Wednesday)

### Recent BA Meeting Discussion

**Quote (Alex):**
> "So that's some sort of a second big idea I was thinking about today, because I was in a meeting on Wednesday, we have this Wednesday meeting, some BA, right? And then we're like, oh, this person, we had conversations, but, you know, they're dabbling, but they don't want to spend 20, 30, $40,000 to build the thing right now. They don't see the justification."

**Problem Identified:**
- BA prospects having conversations
- Dabbling but not committing
- Won't spend $20-40k upfront
- Don't see justification for large investment

### The Solution

**Quote (Alex):**
> "So we can say, hey, you know, we can build these agents and bill you monthly or whatever, and there you go, right? You don't need to put anything up front as an investment, and it can solve your problems."

**Value Proposition:**
- Build agents instead of full custom apps
- Monthly billing
- No upfront investment
- Solves problems immediately

---

## Part 7: Dima's Validation & History

### First Idea Clarification

**Quote (Dima):**
> "So that's the second idea. That was my first idea. That's why I built. That's I built this whole thing. I built it because... I'm not saying it's my unique idea. I'm just saying, I'm sure you thought about it, right?"

**Context:**
- Dima already had this idea
- That's why he built his system
- Not claiming uniqueness
- Acknowledging Alex had same thinking

### BA History & Timing

**Quote (Dima):**
> "No, but I'm truly saying this, that with y'all guys at BA, when everybody was talking about agents and everybody was kind of, like, I was already, you know, I already had the harness. So when people were talking about it, I was itching the whole time because I'm like, yo, I need to, you know..."

**Key Points:**
- While everyone was talking about agents theoretically
- Dima already had working system ("the harness")
- Was "itching" because he was ahead of conversations
- Ready to execute while others discussed concepts

### Strategic Approach Agreement

**Quote (Dima):**
> "So your second approach is that was actually my initial approach to really scale this up. It doesn't have to be order doesn't matter."

**Key Points:**
- Alex's BA approach was Dima's original scaling strategy
- Order of projects doesn't matter
- Alignment on strategy

---

## Part 8: Small Business Applications

### Market Opportunity

**Quote (Dima):**
> "It's just, I think there's a lot of applications in small business, a lot of applications in, like, another opportunity is we're building a voice agent for small businesses right now."

**Key Points:**
- Broad small business applicability
- Voice agent project in progress

### Distribution Channel

**Quote (Dima):**
> "And so we are going to be already in small businesses. We've got this office, our shop. We're going to be already there, talking to them. They want to replace the human phone people with AI. Well, what other what other stuff are you dealing with, right?"

**Distribution Strategy:**
- Already deploying voice agents
- Already in small businesses (offices, shops)
- Replacing human phone staff with AI
- Natural conversation: "What else do you need?"

### Partnership Models

**Quote (Dima):**
> "And that's where we can say, well, we have this other module could be a referral, it could be some sort of partnership, integration, licensing, I don't know, whatever. Or we can be just sort of reseller of it, right? And then there you go. And then that opens up to, you know, a lot of different companies."

**Options Discussed:**
- Referral arrangement
- Partnership
- Integration
- Licensing
- Reseller model
- TBD - flexible on structure

---

## Part 9: Historical Context (Alex)

### Documentation of Use Cases

**Quote (Alex):**
> "Yeah, and I think I already have that in my notes. Because we talked about this. We talked about the network of dentistry that you had. So, you know, early in our days, probably in like May, we were building around your use case that I kind of wrote down for it."

**Key Points:**
- Notes from previous conversations
- Dentistry network discussed
- May 2024 timeframe
- Built around Dima's use cases

**Quote (Alex):**
> "Like, that's why I've always had you in my notes, you know, because, you know, I built things around our initial conversations, but I had to, you know, take a step back and focus on the laboratory."

**Context:**
- Dima always in Alex's planning
- Built features based on their talks
- Had to step back to focus on platform development ("laboratory")

### Mike Wise Connection

**Quote (Alex):**
> "So, and that was with what's his name? The short guy? It's Mike Wise."

**Quote (Dima):**
> "Mike Wise, yes. So do you remember Richard Delgado?"

**Quote (Alex):**
> "Yeah."

**Quote (Dima):**
> "He's my business partner on this."

**Connections:**
- Mike Wise involved in dentistry network
- Richard Delgado is Dima's business partner
- Alex remembers both

---

## Part 10: Richard Delgado Partnership

**Quote (Dima):**
> "Great. Yeah, he's like half, he's like half of what I'm doing right now."

**Key Points:**
- Richard Delgado = 50% of current work
- True partnership, not just collaboration

---

## Part 11: Current Work Mode (Dima)

### Startup Grind Disclaimer

**Quote (Dima):**
> "I've been in the lab. That's why I look like this. I've been in. I'm not in meeting mode right now. You know, I'm currently like, like for example, I pulled a 16 hour work day, probably two days ago. And last night, I stayed up until 5 a.m. working with my partner who's on the West Coast. So his 3 a.m. is our 5 a.m."

**Context:**
- Deep in execution mode ("in the lab")
- Not dressed for meetings
- 16-hour work days recently
- Stayed until 5am working with West Coast partner
- Partner's 3am = his 5am EST

**Quote (Dima):**
> "So I'm in complete startup grind mode..."

---

## Part 12: Alex's Market Research & Preparation

### Detailed Planning

**Quote (Alex):**
> "But I'm saying that, yeah, I had Mike Wise and you, like you always use case because he said, you know, he's doing the voice agents and, you know, you all had the dentistry. So I had the whole, I did a lot of market research on how to build around that stuff."

**Key Points:**
- Mike Wise + Dima use cases documented
- Voice agents research
- Dentistry network research
- Market research conducted

### Attention to Detail

**Quote (Alex):**
> "So, I'm saying all this to say that I have been very paying attention to detail in a lot of the things we've talked about, and I have been preparing capabilities to to basically go into those fronts."

**Key Points:**
- Close attention to all discussions
- Preparing capabilities in advance
- Strategic positioning

### BA Synthesis

**Quote (Alex):**
> "So I've been already synthesizing on how to approach BA. You know, that's kind of why I had to take a step away because I wanted to really make sure that I can position myself properly."

**Reasoning:**
- Already thinking through BA approach
- Took break to position properly
- Strategic preparation time

### Dentistry Network

**Quote (Alex):**
> "And then when it comes to scaling to like the dentistries and things like that, definitely had that in mind, I'd love to talk more about that. I think we can, once we get this POC figured out, I think it'll give us more ground on how to go about those approaches."

**Key Points:**
- Dentistry scaling still in mind
- POC first, then expand
- POC will inform approach to other markets

---

## Part 13: Pricing & Partnership Transparency

### Transparent Pricing Discussion

**Quote (Alex):**
> "But, you know, even when our conversation last time and I told you our prices, yeah, 100%, I mean, I told you our prices because, you know, I'm only being transparent..."

**Key Points:**
- Previous pricing discussion occurred
- Alex being transparent
- Prices already shared

### Partnership Structure

**Quote (Alex):**
> "But the, you know, our partnership together is definitely going to be like us building something and hitting the market together. So, you know, I think the end customer is not like any of us. I think the end customers, the people that we go to, you know."

**Partnership Model:**
- Building together
- Hitting market together
- End customer = the businesses they sell to
- Not vendor/client relationship

### Multi-Front Agreement

**Quote (Alex):**
> "So, um, yeah, I'm all, I'm all for those fronts. I'm down for all three of those."

**Commitment:**
- All three projects (internal, fire dept, BA)
- Full alignment

---

## Part 14: Execution & Timeline

### Dima's Response

**Quote (Dima):**
> "Good. Let's make it happen, now."

### Alex's Current Focus

**Quote (Alex):**
> "Yeah. So I'll get the POC out the way. Like I said, I'm just kind of working on a demo right now that benefits everyone. So, I'm just kind of knocking that out. And probably next week, I'll have a little bit more, more flexibility. Or I'll have a little bit more prepared for us to look at."

**Timeline:**
- Working on demo currently
- Benefits everyone
- Next week: more flexibility
- Next week: more prepared for review

### Key Delivery Promise

**Quote (Alex):**
> "And then I'll give you your keys as soon as possible. So probably over the weekend, I'll give you some keys and I'll give you a simple instruction document and then, yeah. So I'll give you keys and then next week we'll do some testing. Pretty straightforward."

**Commitments:**
- API keys: Over the weekend (Jan 11-12)
- Simple instruction document: Weekend
- Testing: Next week
- Process: "Pretty straightforward"

---

## Part 15: Mutual Enthusiasm

**Quote (Dima):**
> "That was great. Sweet. That was great, man."

**Quote (Alex):**
> "How was BA this week?"

**Quote (Dima):**
> "Just a meeting. It was just a referral meeting. Nothing special. Yeah, we meeting in February, mid February in Dallas next time. So that's the main thing."

**Key Information:**
- BA Dallas meeting: Mid-February 2026
- Previous meeting was just referral (nothing special)
- Next in-person meeting scheduled

**Quote (Alex):**
> "Nice. It was just an online meeting."

**Quote (Dima):**
> "Yeah, everything's good there."

---

## Part 16: Closing & Next Steps

**Quote (Alex):**
> "Nice. Well, cool, man. I'll, like I said, I'll follow up on this, and then let's let's definitely keep going. Everything so, I mean, everything is within scope. It's just execution at this point."

**Key Statement:**
- Alex will follow up
- **"Everything is within scope"**
- **"It's just execution at this point"**
- No technical blockers perceived

**Quote (Dima):**
> "Sounds good. Cool man. Sounds good. I'm looking forward to it. That's all I need. I need that touch. I need to touch it. You know, like you would just to kind of wrap my head around it and then we can take it from there."

**Key Need:**
- **"I need to touch it"**
- Hands-on experience required
- "Wrap my head around it"
- Then can proceed from there

**Quote (Alex):**
> "Yep. It's hot."

**Quote (Dima):**
> "All right. Sounds good."

**Quote (Alex):**
> "All right. Have a good one, man. See ya."

**Quote (Dima):**
> "Yeah. Bye."

---

## Part 17: Post-Call Debrief (Dima's Side)

### Immediate Reaction

**Context:** Dima stayed on the line talking to someone else (likely Richard Delgado or team member)

**Quote (Dima):**
> "Yo, okay. Yo, can you hear me? Yo, oh, my God, I just did two calls back to back, bro."

**Context:**
- Just finished two consecutive calls
- High energy
- Talking to partner/team member

### Call Efficiency Reflection

**Quote (Dima):**
> "What that calls like. No, but that second one, I should I need to do all my calls like that. I need to do all my calls like, hey, you gotta go. Hey, gotta go. I be telling the phone too much, bro. I would probably make a million dollars if I just kept it short."

**Self-Reflection:**
- Wants shorter calls
- Admits he talks too much usually
- Believes brevity = more revenue
- This call was efficient

**Quote (Other Person - likely joking):**
> "Probably, but you already dragged shit on, because you'd be like, yo, and hiding."

**Quote (Dima):**
> "You know, I know. I'd be like, y'all, and howdy."

**Context:**
- Team knows he tends to be long-winded
- Friendly ribbing
- Self-aware about communication style

---

## Part 18: Post-Call Excitement & Realization

### Lock-In Confirmation

**Quote (Dima):**
> "But that's dope. Okay, so we're locked in, bro. Isn't that dope? Did you hear?"

**Key Sentiment:**
- Deal is confirmed ("locked in")
- High enthusiasm
- Seeking validation from listener

### BA History Revelation

**Quote (Dima):**
> "And he owned, bro, BA is the conference where I would do those things and they paid me like $2,000 to shoot the content. Do you not remember that?"

**Background:**
- Dima used to do video/content production at BA
- Got paid $2,000 per event
- Asking if listener remembers this

**Quote (Other Person):**
> "Oh, yeah, I think so."

### Full Circle Moment

**Quote (Dima):**
> "So. That's them. So basically, like, I've quit BA to work on this. And then he just said, we should go to BA and sell it."

**The Story:**
1. Dima quit BA (as contractor/production person)
2. To work on AI agent platform
3. Alex (in this call) suggested going to BA to sell agents
4. Full circle back to BA

**Quote (Dima):**
> "I have a feeling that he's going to bring me back to BA and instead of me going to BA as a fucking as a fucking, as a fucking audio person or whatever, a production person, I'm going back to BA as a damn fucking business person."

**Emotional Significance:**
- Left BA as production contractor ($2k/event)
- Returning as business owner/partner
- Status upgrade: production → business owner
- High emotional investment in this transformation

### The Magnitude

**Quote (Dima):**
> "Full circle, nigga. Bro, I don't think you understand what's going on, dog. Oh. I don't bro."

**Emotional State:**
- Deeply excited
- Feels listener doesn't understand significance
- "Full circle" transformation
- Life-changing moment recognition

---

## Meeting Transcript Summary

### Timeline
- **May 2024:** Initial conversations, dentistry network, Mike Wise collaboration
- **During:** Alex stepped back to build platform ("laboratory")
- **December 2024/Early 2025:** Dima building his own agentic system
- **January 9, 2026:** This call - POC discussion
- **This Weekend (Jan 11-12):** API keys delivery
- **Next Week (Jan 13+):** Testing begins
- **Mid-February 2026:** BA Dallas conference

### Key Deliverables Promised
1. API keys (this weekend)
2. Simple instruction document (this weekend)
3. More prepared demo (next week)
4. Testing session (next week)

### Three Projects Confirmed
1. **Internal Admin Automation** - Immediate, proof of concept
2. **Fire Department Scheduling** - Enterprise scale, tens of thousands of departments
3. **BA Network & Small Business** - Distribution channel, voice agent upsells

### Key Relationships
- **Dima Semyansky** - Lead, builder, BA network access
- **Richard Delgado** - 50/50 business partner
- **Mike Wise** - Voice agents, dentistry network
- **Alex Mayo** - Platform provider, partnership approach

### Tone & Energy
- **Startup grind mode** - 16-hour days, 5am work sessions
- **High enthusiasm** - "Locked in," "let's make it happen"
- **Hands-on requirement** - "I need to touch it"
- **Emotional investment** - BA "full circle" story
- **Execution focus** - "Everything is within scope, it's just execution"

### Critical Success Factor
**Quote (Dima):** "I need to touch it. You know, like you would just to kind of wrap my head around it and then we can take it from there."

**Everything hinges on Dima getting hands-on access this weekend.**

---

**Transcript Summary Completed**  
**Date:** January 9, 2026  
**Documented By:** Alex Mayo  
**Status:** Ready for analysis and recommendations (see separate document)
